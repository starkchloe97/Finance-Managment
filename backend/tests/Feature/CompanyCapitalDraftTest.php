<?php

namespace Tests\Feature;

use App\Enums\CompanyCapitalDraftStatus;
use App\Models\CompanyCapitalDraft;
use App\Models\CompanyCapitalDraftActivity;
use App\Models\Investor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CompanyCapitalDraftTest extends TestCase
{
    use RefreshDatabase;

    private $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = \App\Models\User::factory()->create();
        Sanctum::actingAs($this->user);
    }

    private function initializeCapital(float $amount)
    {
        return $this->postJson('/api/v1/company-capital/initialize', [
            'amount' => $amount,
            'transaction_date' => today()->toDateString(),
        ]);
    }

    private function createDraft(float $amount, string $note): array
    {
        return $this->postJson('/api/v1/company-capital', [
            'amount' => $amount,
            'transaction_date' => today()->toDateString(),
            'status' => 'draft',
            'notes' => $note,
        ])->assertOk()->json('data');
    }

    private function createInvestor(): Investor
    {
        return Investor::create([
            'investor_code' => 'INV-'.str_pad((string) (Investor::count() + 1), 6, '0', STR_PAD_LEFT),
            'name' => 'Test Investor '.(Investor::count() + 1),
            'email' => 'investor'.(Investor::count() + 1).'@example.com',
            'status' => 'active',
        ]);
    }

    private function investorLoanPayload(Investor $investor, float $amount, array $overrides = []): array
    {
        return [
            'borrower_type' => 'investor',
            'investor_id' => $investor->id,
            'amount' => $amount,
            'loan_date' => today()->toDateString(),
            'due_date' => today()->addMonth()->toDateString(),
            ...$overrides,
        ];
    }

    public function test_draft_creation_does_not_change_capital(): void
    {
        $this->initializeCapital(100000);

        $this->createDraft(50000, 'Expected owner funding next week');

        $this->assertDatabaseCount('company_capital_drafts', 1);
        $this->assertDatabaseCount('company_capital_draft_activities', 1);
        $this->assertDatabaseHas('company_capital_drafts', [
            'amount' => 50000,
            'status' => CompanyCapitalDraftStatus::Draft->value,
            'note' => 'Expected owner funding next week',
        ]);
        $this->assertDatabaseHas('company_capital_draft_activities', [
            'activity_type' => 'added',
            'note' => 'Expected owner funding next week',
        ]);

        $this->getJson('/api/v1/company-capital')
            ->assertJsonPath('data.available_to_lend', 100000)
            ->assertJsonPath('data.total_capital', 100000)
            ->assertJsonPath('data.reserved', 0)
            ->assertJsonPath('data.current_balance', 100000);
    }

    public function test_draft_appears_in_pending_drafts(): void
    {
        $this->initializeCapital(100000);

        $this->createDraft(50000, 'Expected owner funding next week');

        $response = $this->getJson('/api/v1/company-capital');

        $response->assertOk();
        $this->assertCount(1, $response->json('data.drafts'));
        $this->assertSame('50000.00', $response->json('data.drafts.0.amount'));
        $this->assertNotNull($response->json('data.drafts.0.transaction_code'));
    }

    public function test_draft_added_appears_in_draft_history(): void
    {
        $this->initializeCapital(100000);

        $this->createDraft(50000, 'Expected owner funding next week');

        $history = $this->getJson('/api/v1/company-capital')->json('data.draft_history');

        $this->assertCount(1, $history);
        $this->assertSame('capital_draft', $history[0]['type']);
        $this->assertSame('added', $history[0]['draft_status']);
        $this->assertSame('Expected owner funding next week', $history[0]['description']);
        $this->assertSame('50000.00', $history[0]['amount']);
    }

    public function test_convert_draft_to_available_increases_available_and_total_capital(): void
    {
        $this->initializeCapital(100000);

        $draftId = $this->createDraft(50000, 'Expected owner funding')['drafts'][0]['id'];

        $this->postJson("/api/v1/company-capital/drafts/{$draftId}/convert", [
            'available' => true,
        ])->assertOk();

        $this->assertDatabaseHas('company_capital_drafts', [
            'id' => $draftId,
            'status' => CompanyCapitalDraftStatus::Converted->value,
        ]);
        $this->assertDatabaseHas('company_capital_draft_activities', [
            'company_capital_draft_id' => $draftId,
            'activity_type' => 'converted',
        ]);

        $this->assertDatabaseCount('company_capital_transactions', 2);
        $this->assertDatabaseHas('company_capital_transactions', [
            'type' => 'capital_added',
            'amount' => 50000,
        ]);

        $this->getJson('/api/v1/company-capital')
            ->assertJsonPath('data.available_to_lend', 150000)
            ->assertJsonPath('data.total_capital', 150000)
            ->assertJsonPath('data.lent_out', 0)
            ->assertJsonPath('data.reserved', 0);

        $this->getJson('/api/v1/company-capital')
            ->assertJsonPath('data.drafts', []);
    }

    public function test_convert_draft_to_reserved_increases_reserved_and_total_capital(): void
    {
        $this->initializeCapital(100000);

        $draftId = $this->createDraft(50000, 'Expected owner funding')['drafts'][0]['id'];

        $this->postJson("/api/v1/company-capital/drafts/{$draftId}/convert", [
            'available' => false,
        ])->assertOk();

        $this->getJson('/api/v1/company-capital')
            ->assertJsonPath('data.available_to_lend', 100000)
            ->assertJsonPath('data.total_capital', 150000)
            ->assertJsonPath('data.reserved', 50000)
            ->assertJsonPath('data.lent_out', 0);

        $this->assertMoney(100000, $this->getJson('/api/v1/company-capital')->json('data.current_balance'));
    }

    public function test_remove_draft_does_not_change_capital(): void
    {
        $this->initializeCapital(100000);

        $draftId = $this->createDraft(50000, 'Expected owner funding')['drafts'][0]['id'];

        $this->postJson("/api/v1/company-capital/drafts/{$draftId}/remove", [
            'removal_note' => 'Owner changed mind about this funding',
        ])->assertOk();

        $this->assertDatabaseHas('company_capital_drafts', [
            'id' => $draftId,
            'status' => CompanyCapitalDraftStatus::Removed->value,
            'removal_note' => 'Owner changed mind about this funding',
        ]);

        $this->assertDatabaseCount('company_capital_transactions', 1);

        $this->getJson('/api/v1/company-capital')
            ->assertJsonPath('data.available_to_lend', 100000)
            ->assertJsonPath('data.total_capital', 100000)
            ->assertJsonPath('data.current_balance', 100000);

        $this->getJson('/api/v1/company-capital')
            ->assertJsonPath('data.drafts', []);
    }

    public function test_remove_draft_requires_a_note(): void
    {
        $this->initializeCapital(100000);

        $draftId = $this->createDraft(50000, 'Expected owner funding')['drafts'][0]['id'];

        $this->postJson("/api/v1/company-capital/drafts/{$draftId}/remove", [
            'removal_note' => '',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('removal_note');

        $this->assertDatabaseHas('company_capital_drafts', [
            'id' => $draftId,
            'status' => CompanyCapitalDraftStatus::Draft->value,
        ]);
    }

    public function test_converted_draft_cannot_be_converted_again(): void
    {
        $this->initializeCapital(100000);

        $draftId = $this->createDraft(50000, 'Expected owner funding')['drafts'][0]['id'];

        $this->postJson("/api/v1/company-capital/drafts/{$draftId}/convert", [
            'available' => true,
        ])->assertOk();

        $this->postJson("/api/v1/company-capital/drafts/{$draftId}/convert", [
            'available' => false,
        ])->assertNotFound();
    }

    public function test_removed_draft_cannot_be_converted(): void
    {
        $this->initializeCapital(100000);

        $draftId = $this->createDraft(50000, 'Expected owner funding')['drafts'][0]['id'];

        $this->postJson("/api/v1/company-capital/drafts/{$draftId}/remove", [
            'removal_note' => 'No longer needed',
        ])->assertOk();

        $this->postJson("/api/v1/company-capital/drafts/{$draftId}/convert", [
            'available' => true,
        ])->assertNotFound();
    }

    public function test_draft_does_not_affect_loan_eligibility(): void
    {
        $this->initializeCapital(10000);

        $this->createDraft(100000, 'Expected funding');

        $this->getJson('/api/v1/company-capital')
            ->assertJsonPath('data.available_to_lend', 10000)
            ->assertJsonPath('data.total_capital', 10000);

        $investor = $this->createInvestor();

        $this->postJson('/api/v1/loans', $this->investorLoanPayload($investor, 50000))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('capital');

        $this->assertDatabaseCount('loans', 0);
        $this->assertDatabaseCount('company_capital_transactions', 1);
    }

    public function test_reconciliation_after_multiple_drafts_and_conversions(): void
    {
        $this->initializeCapital(15000);

        $reservedDraftId = $this->createDraft(12000, 'Reserved funding')['drafts'][0]['id'];

        $this->createDraft(17000, 'Available funding');

        $availableDraftId = $this->createDraft(10000, 'More available funding')['drafts'][0]['id'];

        $this->postJson("/api/v1/company-capital/drafts/{$reservedDraftId}/convert", [
            'available' => false,
        ])->assertOk();

        $this->postJson("/api/v1/company-capital/drafts/{$availableDraftId}/convert", [
            'available' => true,
        ])->assertOk();

        $snapshot = $this->getJson('/api/v1/company-capital')->json('data');

        $this->assertSame(25000.0, (float) $snapshot['available_to_lend']);
        $this->assertSame(12000.0, (float) $snapshot['reserved']);
        $this->assertSame(37000.0, (float) $snapshot['total_capital']);
        $this->assertSame(
            (float) $snapshot['total_capital'],
            (float) $snapshot['available_to_lend'] + (float) $snapshot['lent_out'] + (float) $snapshot['reserved']
        );
        $this->assertCount(1, $snapshot['drafts']);
    }

    public function test_remove_draft_creates_audit_entry_and_preserves_original(): void
    {
        $this->initializeCapital(100000);

        $draftId = $this->createDraft(50000, 'Expected owner funding next week')['drafts'][0]['id'];

        $this->postJson("/api/v1/company-capital/drafts/{$draftId}/remove", [
            'removal_note' => 'Funding postponed',
        ])->assertOk();

        $history = $this->getJson('/api/v1/company-capital')->json('data.draft_history');

        $this->assertCount(2, $history);
        $removedEvent = $history[0];
        $addedEvent = $history[1];

        $this->assertSame('removed', $removedEvent['draft_status']);
        $this->assertSame('Draft removed', $this->movementLabelForTest($removedEvent['draft_status']));
        $this->assertSame('Funding postponed', $removedEvent['description']);

        $this->assertSame('added', $addedEvent['draft_status']);
        $this->assertSame('Expected owner funding next week', $addedEvent['description']);
        $this->assertSame('50000.00', $addedEvent['amount']);
    }

    public function test_convert_draft_creates_audit_entry_and_preserves_original(): void
    {
        $this->initializeCapital(100000);

        $draftId = $this->createDraft(50000, 'Owner funding expected')['drafts'][0]['id'];

        $this->postJson("/api/v1/company-capital/drafts/{$draftId}/convert", [
            'available' => true,
        ])->assertOk();

        $history = $this->getJson('/api/v1/company-capital')->json('data.draft_history');

        $this->assertCount(2, $history);
        $convertedEvent = $history[0];
        $addedEvent = $history[1];

        $this->assertSame('converted', $convertedEvent['draft_status']);
        $this->assertSame('Added as available company capital', $convertedEvent['description']);
        $this->assertSame('added', $addedEvent['draft_status']);
        $this->assertSame('Owner funding expected', $addedEvent['description']);
    }

    private function movementLabelForTest(string $status): string
    {
        return [
            'added' => 'Capital draft',
            'converted' => 'Draft converted',
            'removed' => 'Draft removed',
        ][$status] ?? $status;
    }
}
