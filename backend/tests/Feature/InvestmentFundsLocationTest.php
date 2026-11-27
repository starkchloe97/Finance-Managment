<?php

namespace Tests\Feature;

use App\Models\BankAccount;
use App\Models\Investor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InvestmentFundsLocationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Sanctum::actingAs(User::factory()->create());
    }

    public function test_bank_investment_credits_the_selected_bank_account(): void
    {
        $investor = $this->investor();
        $bankAccount = BankAccount::create([
            'name' => 'Operating account',
            'opening_balance' => 1_000,
            'current_balance' => 1_000,
        ]);

        $response = $this->postJson('/api/v1/investments', $this->payload($investor->id, [
            'funds_location' => 'bank',
            'bank_account_id' => $bankAccount->id,
        ]));

        $response
            ->assertCreated()
            ->assertJsonPath('data.funds_location', 'bank')
            ->assertJsonPath('data.bank_account_id', $bankAccount->id)
            ->assertJsonPath('data.bank_account.name', 'Operating account');

        $this->assertDatabaseHas('bank_accounts', [
            'id' => $bankAccount->id,
            'current_balance' => 1500,
        ]);
        $this->assertDatabaseHas('bank_transactions', [
            'bank_account_id' => $bankAccount->id,
            'type' => 'investment_receipt',
            'amount' => 500,
            'balance_after' => 1500,
        ]);
    }

    public function test_bank_location_requires_a_bank_account_and_adjustment_is_not_accepted(): void
    {
        $investor = $this->investor();

        $this->postJson('/api/v1/investments', $this->payload($investor->id, [
            'funds_location' => 'bank',
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('bank_account_id');

        $this->postJson('/api/v1/investments', $this->payload($investor->id, [
            'funds_location' => 'adjustment',
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('funds_location');
    }

    private function investor(): Investor
    {
        return Investor::create([
            'investor_code' => 'INV-000001',
            'name' => 'Investor',
        ]);
    }

    private function payload(int $investorId, array $overrides = []): array
    {
        return array_merge([
            'investor_id' => $investorId,
            'investment_date' => '2026-11-27',
            'amount' => 500,
            'funds_location' => 'hand',
            'bank_account_id' => null,
            'investment_category' => 'normal',
            'return_type' => 'percentage',
            'return_percentage' => 2,
            'fixed_return_amount' => null,
            'period_months' => 6,
        ], $overrides);
    }
}
