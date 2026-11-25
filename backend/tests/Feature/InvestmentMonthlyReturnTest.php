<?php

namespace Tests\Feature;

use App\Enums\InvestmentCategory;
use App\Enums\InvestmentReturnType;
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
            "/api/v1/investments/{$investment->id}/monthly-returns/{$returns[0]['id']}/pay"
        )
            ->assertOk()
            ->assertJsonPath('data.status', 'paid')
            ->assertJsonPath('data.paid_amount', '2000.00');

        $this->postJson(
            "/api/v1/investments/{$investment->id}/monthly-returns/{$returns[0]['id']}/pay"
        )
            ->assertStatus(422)
            ->assertJsonValidationErrors('monthly_return');
    }
}
