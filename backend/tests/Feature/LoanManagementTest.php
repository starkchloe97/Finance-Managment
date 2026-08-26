<?php

namespace Tests\Feature;

use App\Enums\CompanyCapitalTransactionType;
use App\Models\CompanyCapitalTransaction;
use App\Models\Investor;
use App\Models\Loan;
use App\Models\LoanBorrower;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LoanManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    }

    public function test_loan_and_capital_endpoints_require_authentication(): void
    {
        auth()->guard('web')->logout();
        $this->app['auth']->forgetGuards();

        $this->getJson('/api/v1/company-capital')->assertUnauthorized();
        $this->getJson('/api/v1/loans')->assertUnauthorized();
        $this->postJson('/api/v1/loan-borrowers', [])->assertUnauthorized();
    }

    public function test_capital_initializes_once(): void
    {
        $this->getJson('/api/v1/company-capital')
            ->assertOk()
            ->assertJsonPath('data.initialized', false)
            ->assertJsonPath('data.current_balance', 0);

        $this->initializeCapital(100000)
            ->assertOk()
            ->assertJsonPath('data.initialized', true)
            ->assertJsonPath('data.opening_balance', 100000)
            ->assertJsonPath('data.current_balance', 100000);

        $this->initializeCapital(200000)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('amount');

        $this->assertDatabaseCount('company_capital_transactions', 1);
    }

    public function test_investor_loan_reduces_capital_and_multiple_loans_are_independent(): void
    {
        $this->initializeCapital(1000000);
        $investor = $this->createInvestor();

        $first = $this->postJson('/api/v1/loans', $this->investorLoanPayload($investor, 100000))
            ->assertOk()
            ->assertJsonPath('data.loan_code', 'LOAN-000001')
            ->assertJsonPath('data.borrower_type', 'investor')
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.outstanding_amount', 100000)
            ->json('data');

        $second = $this->postJson('/api/v1/loans', $this->investorLoanPayload($investor, 50000))
            ->assertOk()
            ->assertJsonPath('data.loan_code', 'LOAN-000002')
            ->json('data');

        $this->assertNotSame($first['id'], $second['id']);
        $this->assertDatabaseCount('loans', 2);
        $this->assertMoney(-150000, CompanyCapitalTransaction::query()
            ->where('type', CompanyCapitalTransactionType::LoanIssued->value)
            ->sum('amount'));
        $this->getJson('/api/v1/company-capital')->assertJsonPath('data.current_balance', 850000);
    }

    public function test_existing_and_inline_outsiders_are_supported_without_becoming_investors(): void
    {
        $this->initializeCapital(300000);
        $borrower = LoanBorrower::create([
            'borrower_code' => 'BOR-000001',
            'name' => 'Existing Borrower',
            'phone' => '111',
        ]);

        $this->postJson('/api/v1/loans', $this->outsiderLoanPayload(50000, [
            'loan_borrower_id' => $borrower->id,
        ]))->assertOk()->assertJsonPath('data.borrower.id', $borrower->id);

        $this->postJson('/api/v1/loans', $this->outsiderLoanPayload(40000, [
            'outsider_name' => 'New Borrower',
            'outsider_email' => 'new@example.com',
        ]))->assertOk()->assertJsonPath('data.borrower.name', 'New Borrower');

        $this->assertDatabaseCount('loan_borrowers', 2);
        $this->assertDatabaseCount('investors', 0);
    }

    public function test_ambiguous_or_missing_outsider_selection_is_rejected_without_writes(): void
    {
        $this->initializeCapital(100000);
        $borrower = LoanBorrower::create([
            'borrower_code' => 'BOR-000001',
            'name' => 'Existing Borrower',
        ]);

        $this->postJson('/api/v1/loans', $this->outsiderLoanPayload(10000, [
            'loan_borrower_id' => $borrower->id,
            'outsider_name' => 'Duplicate Mode',
        ]))->assertUnprocessable()->assertJsonValidationErrors(['loan_borrower_id', 'outsider_name']);

        $this->postJson('/api/v1/loans', $this->outsiderLoanPayload(10000))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['loan_borrower_id', 'outsider_name']);

        $this->assertDatabaseCount('loans', 0);
        $this->assertDatabaseCount('company_capital_transactions', 1);
    }

    public function test_uninitialized_and_insufficient_capital_reject_issuance_atomically(): void
    {
        $investor = $this->createInvestor();

        $this->postJson('/api/v1/loans', $this->investorLoanPayload($investor, 10000))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('capital');

        $this->initializeCapital(5000);
        $this->postJson('/api/v1/loans', $this->investorLoanPayload($investor, 10000))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('amount');

        $this->assertDatabaseCount('loans', 0);
        $this->assertDatabaseCount('company_capital_transactions', 1);
    }

    public function test_partial_and_full_repayments_restore_capital_and_close_the_loan(): void
    {
        $loan = $this->createInvestorLoan(100000, 1000000);

        $this->postJson("/api/v1/loans/{$loan->id}/repayments", [
            'amount' => 40000,
            'payment_date' => today()->toDateString(),
            'reference' => 'PAY-1',
        ])->assertCreated()->assertJsonPath('data.amount', '40000.00');

        $this->getJson("/api/v1/loans/{$loan->id}")
            ->assertOk()
            ->assertJsonPath('data.total_repaid', 40000)
            ->assertJsonPath('data.outstanding_amount', 60000)
            ->assertJsonPath('data.status', 'active');

        $this->postJson("/api/v1/loans/{$loan->id}/repayments", [
            'amount' => 60000,
            'payment_date' => today()->toDateString(),
        ])->assertCreated();

        $this->getJson("/api/v1/loans/{$loan->id}")
            ->assertJsonPath('data.total_repaid', 100000)
            ->assertJsonPath('data.outstanding_amount', 0)
            ->assertJsonPath('data.status', 'paid')
            ->assertJsonPath('data.paid_at', fn ($value) => is_string($value));

        $this->getJson('/api/v1/company-capital')->assertJsonPath('data.current_balance', 1000000);
        $this->assertDatabaseCount('loan_repayments', 2);
    }

    public function test_overpayment_is_rejected_without_financial_changes(): void
    {
        $loan = $this->createInvestorLoan(100000, 200000);

        $this->postJson("/api/v1/loans/{$loan->id}/repayments", [
            'amount' => 100001,
            'payment_date' => today()->toDateString(),
        ])->assertUnprocessable()->assertJsonValidationErrors('amount');

        $this->assertDatabaseCount('loan_repayments', 0);
        $this->assertDatabaseCount('company_capital_transactions', 2);
        $this->getJson('/api/v1/company-capital')->assertJsonPath('data.current_balance', 100000);
    }

    public function test_overdue_repayment_and_due_date_extension_preserve_history_without_extra_deduction(): void
    {
        $this->initializeCapital(200000);
        $investor = $this->createInvestor();
        $response = $this->postJson('/api/v1/loans', $this->investorLoanPayload($investor, 100000, [
            'loan_date' => today()->subDays(10)->toDateString(),
            'due_date' => today()->subDay()->toDateString(),
        ]))->assertOk();
        $loanId = $response->json('data.id');

        $this->getJson('/api/v1/loans?status=overdue')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'overdue')
            ->assertJsonPath('data.0.first_overdue_at', fn ($value) => is_string($value));
        $this->assertDatabaseCount('company_capital_transactions', 2);

        $this->postJson("/api/v1/loans/{$loanId}/repayments", [
            'amount' => 40000,
            'payment_date' => today()->toDateString(),
        ])->assertCreated();
        $this->getJson("/api/v1/loans/{$loanId}")
            ->assertJsonPath('data.status', 'overdue')
            ->assertJsonPath('data.outstanding_amount', 60000);

        $firstOverdueAt = Loan::findOrFail($loanId)->first_overdue_at;
        $this->putJson("/api/v1/loans/{$loanId}", [
            'due_date' => today()->addWeek()->toDateString(),
            'notes' => 'Extended',
        ])->assertOk()->assertJsonPath('data.status', 'active');

        $this->assertTrue(Loan::findOrFail($loanId)->first_overdue_at->equalTo($firstOverdueAt));
        $this->assertDatabaseCount('company_capital_transactions', 3);
    }

    public function test_cancelled_unrepaid_loan_restores_capital_once_and_has_no_current_obligation(): void
    {
        $loan = $this->createInvestorLoan(100000, 200000);

        $this->postJson("/api/v1/loans/{$loan->id}/cancel")
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled')
            ->assertJsonPath('data.outstanding_amount', 0);

        $this->getJson('/api/v1/company-capital')->assertJsonPath('data.current_balance', 200000);
        $this->postJson("/api/v1/loans/{$loan->id}/cancel")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('loan');

        $this->assertSame(1, CompanyCapitalTransaction::query()
            ->where('type', CompanyCapitalTransactionType::LoanCancelled->value)
            ->count());
    }

    public function test_cancel_after_repayment_and_repayment_after_paid_are_rejected(): void
    {
        $loan = $this->createInvestorLoan(100000, 200000);
        $this->postJson("/api/v1/loans/{$loan->id}/repayments", [
            'amount' => 50000,
            'payment_date' => today()->toDateString(),
        ])->assertCreated();

        $this->postJson("/api/v1/loans/{$loan->id}/cancel")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('loan');

        $this->postJson("/api/v1/loans/{$loan->id}/repayments", [
            'amount' => 50000,
            'payment_date' => today()->toDateString(),
        ])->assertCreated();
        $this->postJson("/api/v1/loans/{$loan->id}/repayments", [
            'amount' => 1,
            'payment_date' => today()->toDateString(),
        ])->assertUnprocessable()->assertJsonValidationErrors('loan');

        $this->assertDatabaseCount('loan_repayments', 2);
    }

    public function test_investor_loan_metadata_is_complete_and_investor_deletion_is_blocked(): void
    {
        $this->initializeCapital(500000);
        $investor = $this->createInvestor();

        $active = $this->postJson('/api/v1/loans', $this->investorLoanPayload($investor, 100000))->json('data');
        $paid = $this->postJson('/api/v1/loans', $this->investorLoanPayload($investor, 50000))->json('data');
        $this->postJson("/api/v1/loans/{$paid['id']}/repayments", [
            'amount' => 50000,
            'payment_date' => today()->toDateString(),
        ]);

        $this->getJson("/api/v1/investors/{$investor->id}/loans?per_page=1")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('meta.loan_totals.issued', 150000)
            ->assertJsonPath('meta.loan_totals.repaid', 50000)
            ->assertJsonPath('meta.loan_totals.outstanding', 100000)
            ->assertJsonPath('meta.loan_totals.active', 1)
            ->assertJsonPath('meta.loan_totals.paid', 1);

        $this->deleteJson("/api/v1/investors/{$investor->id}")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('investor');
        $this->assertNull($investor->fresh()->deleted_at);
        $this->assertDatabaseHas('loans', ['id' => $active['id']]);
    }

    private function initializeCapital(float $amount)
    {
        return $this->postJson('/api/v1/company-capital/initialize', [
            'amount' => $amount,
            'transaction_date' => today()->toDateString(),
        ]);
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

    private function createInvestorLoan(float $amount, float $capital): Loan
    {
        $this->initializeCapital($capital);
        $investor = $this->createInvestor();
        $loanId = $this->postJson('/api/v1/loans', $this->investorLoanPayload($investor, $amount))
            ->assertOk()
            ->json('data.id');

        return Loan::findOrFail($loanId);
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

    private function outsiderLoanPayload(float $amount, array $borrower = []): array
    {
        return [
            'borrower_type' => 'outsider',
            'amount' => $amount,
            'loan_date' => today()->toDateString(),
            'due_date' => today()->addMonth()->toDateString(),
            ...$borrower,
        ];
    }

    private function assertMoney(float $expected, mixed $actual): void
    {
        $this->assertSame($expected, (float) $actual);
    }
}
