<?php

namespace Tests\Feature;

use App\Enums\InvestmentCategory;
use App\Enums\InvestmentReturnType;
use App\Models\BankAccount;
use App\Models\Investment;
use App\Models\Investor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InvestmentMonthlyReturnTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_an_investment_generates_and_pays_monthly_returns(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $investor = Investor::factory()->create();

        $response = $this->postJson('/api/v1/investments', [
            'investor_id' => $investor->id,
            'investment_date' => today()->subMonthsNoOverflow(3)->toDateString(),
            'amount' => 100000,
            'investment_category' => InvestmentCategory::Normal->value,
            'return_type' => InvestmentReturnType::Percentage->value,
            'return_percentage' => 2,
            'period_months' => 3,
            'deduction_amount' => 500,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.monthly_return_amount', 2000)
            ->assertJsonPath('data.total_scheduled_return_amount', 6000)
            ->assertJsonPath('data.expected_settlement_amount', 99500);

        $investment = Investment::findOrFail($response->json('data.id'));

        $this->assertDatabaseCount('investment_monthly_returns', 3);

        $returns = $this->getJson("/api/v1/investments/{$investment->id}/monthly-returns")
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->json('data');

        $this->postJson(
            "/api/v1/investments/{$investment->id}/monthly-returns/{$returns[0]['id']}/pay",
            ['payout_location' => 'hand']
        )
            ->assertOk()
            ->assertJsonPath('data.status', 'paid')
            ->assertJsonPath('data.paid_amount', '2000.00');

        $this->postJson(
            "/api/v1/investments/{$investment->id}/monthly-returns/{$returns[0]['id']}/pay",
            ['payout_location' => 'hand']
        )
            ->assertStatus(422)
            ->assertJsonValidationErrors('monthly_return');
    }

    public function test_monthly_return_defaults_to_cash_in_hand_when_location_is_omitted(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $investor = Investor::factory()->create();

        $response = $this->postJson('/api/v1/investments', [
            'investor_id' => $investor->id,
            'investment_date' => today()->subMonthsNoOverflow(1)->toDateString(),
            'amount' => 10000,
            'funds_location' => 'hand',
            'investment_category' => InvestmentCategory::Normal->value,
            'return_type' => InvestmentReturnType::Percentage->value,
            'return_percentage' => 2,
            'period_months' => 1,
        ])->assertCreated();

        $investment = Investment::findOrFail($response->json('data.id'));
        $monthlyReturn = $investment->monthlyReturns()->firstOrFail();

        $this->postJson(
            "/api/v1/investments/{$investment->id}/monthly-returns/{$monthlyReturn->id}/pay"
        )
            ->assertOk()
            ->assertJsonPath('data.status', 'paid')
            ->assertJsonPath('data.payout_location', 'hand')
            ->assertJsonPath('data.bank_account_id', null);
    }

    public function test_monthly_return_paid_from_bank_debits_the_selected_account(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $investor = Investor::factory()->create();
        $bank = BankAccount::create([
            'name' => 'Operating account',
            'opening_balance' => 10000,
            'current_balance' => 10000,
        ]);

        $response = $this->postJson('/api/v1/investments', [
            'investor_id' => $investor->id,
            'investment_date' => today()->subMonthsNoOverflow(1)->toDateString(),
            'amount' => 100000,
            'funds_location' => 'hand',
            'bank_account_id' => null,
            'investment_category' => InvestmentCategory::Normal->value,
            'return_type' => InvestmentReturnType::Percentage->value,
            'return_percentage' => 2,
            'period_months' => 1,
        ])->assertCreated();

        $investment = Investment::findOrFail($response->json('data.id'));
        $monthlyReturn = $investment->monthlyReturns()->firstOrFail();

        $this->postJson(
            "/api/v1/investments/{$investment->id}/monthly-returns/{$monthlyReturn->id}/pay",
            [
                'payout_location' => 'bank',
                'bank_account_id' => $bank->id,
            ]
        )
            ->assertOk()
            ->assertJsonPath('data.payout_location', 'bank')
            ->assertJsonPath('data.bank_account_id', $bank->id);

        $this->assertDatabaseHas('bank_accounts', [
            'id' => $bank->id,
            'current_balance' => 8000,
        ]);
        $this->assertDatabaseHas('bank_transactions', [
            'bank_account_id' => $bank->id,
            'investment_id' => $investment->id,
            'type' => 'investment_monthly_payout',
            'amount' => -2000,
            'balance_after' => 8000,
        ]);
    }
}
