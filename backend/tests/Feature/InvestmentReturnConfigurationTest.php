<?php

namespace Tests\Feature;

use App\Enums\InvestmentCategory;
use App\Enums\InvestmentReturnType;
use App\Models\Customer;
use App\Models\Estimate;
use App\Models\Investment;
use App\Models\InvestmentAllocation;
use App\Models\Investor;
use App\Models\TransportJob;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InvestmentReturnConfigurationTest extends TestCase
{
    use RefreshDatabase;

    public function test_percentage_investment_uses_authoritative_derived_return(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $investor = $this->investor();

        $response = $this->postJson('/api/v1/investments', [
            'investor_id' => $investor->id,
            'investment_date' => '2026-08-21',
            'amount' => 500000,
            'investment_category' => 'pool',
            'return_type' => 'percentage',
            'return_percentage' => 10,
            'fixed_return_amount' => null,
            'calculated_return_amount' => 1,
            'period_months' => 6,
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors('calculated_return_amount');

        $response = $this->postJson('/api/v1/investments', [
            'investor_id' => $investor->id,
            'investment_date' => '2026-08-21',
            'amount' => 500000,
            'investment_category' => 'pool',
            'return_type' => 'percentage',
            'return_percentage' => 10,
            'fixed_return_amount' => null,
            'period_months' => 6,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.investment_category', 'pool')
            ->assertJsonPath('data.return_type', 'percentage')
            ->assertJsonPath('data.return_percentage', '10.00')
            ->assertJsonPath('data.fixed_return_amount', null)
            ->assertJsonPath('data.calculated_return_amount', 50000);
    }

    public function test_fixed_investment_requires_fixed_amount_and_clears_percentage(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $investor = $this->investor();

        $response = $this->postJson('/api/v1/investments', [
            'investor_id' => $investor->id,
            'investment_date' => '2026-08-21',
            'amount' => 500000,
            'investment_category' => 'normal',
            'return_type' => 'fixed',
            'return_percentage' => 10,
            'fixed_return_amount' => 50000,
            'period_months' => 6,
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors('return_percentage');

        $response = $this->postJson('/api/v1/investments', [
            'investor_id' => $investor->id,
            'investment_date' => '2026-08-21',
            'amount' => 500000,
            'investment_category' => 'normal',
            'return_type' => 'fixed',
            'return_percentage' => null,
            'fixed_return_amount' => 50000,
            'period_months' => 6,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.return_type', 'fixed')
            ->assertJsonPath('data.return_percentage', null)
            ->assertJsonPath('data.fixed_return_amount', '50000.00')
            ->assertJsonPath('data.calculated_return_amount', 50000);
    }

    public function test_pool_investment_only_allows_percentage_returns(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $investor = $this->investor();

        $this->postJson('/api/v1/investments', [
            'investor_id' => $investor->id,
            'investment_date' => '2026-08-21',
            'amount' => 500000,
            'investment_category' => 'pool',
            'return_type' => 'fixed',
            'return_percentage' => null,
            'fixed_return_amount' => 50000,
            'period_months' => 6,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['return_type', 'fixed_return_amount']);
    }

    public function test_pool_investment_cannot_be_allocated_to_a_job(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $investment = $this->investment(['investment_category' => InvestmentCategory::Pool]);
        $customer = Customer::create([
            'code' => 'CUS-000001',
            'name' => 'Customer',
        ]);
        $estimate = Estimate::create([
            'code' => 'EST-000001',
            'customer_id' => $customer->id,
            'estimate_date' => '2026-08-21',
            'pickup' => 'Pickup',
            'destination' => 'Destination',
            'service_type' => 'goods',
            'estimated_sell' => 120000,
            'estimated_cost' => 100000,
            'estimated_profit' => 20000,
            'status' => 'accepted',
        ]);
        $job = TransportJob::create([
            'code' => 'JOB-000001',
            'estimate_id' => $estimate->id,
            'customer_id' => $customer->id,
            'job_date' => '2026-08-21',
            'status' => 'draft',
            'sell_price' => 120000,
            'cost_price' => 100000,
            'base_profit' => 20000,
            'extra_costs' => 0,
            'final_profit' => 20000,
        ]);

        $this->postJson("/api/v1/investments/{$investment->id}/allocations", [
            'transport_job_id' => $job->id,
            'amount' => 100000,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('investment');
    }

    public function test_percentage_investment_requires_percentage_and_prohibits_fixed_amount(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $investor = $this->investor();

        $this->postJson('/api/v1/investments', [
            'investor_id' => $investor->id,
            'investment_date' => '2026-08-21',
            'amount' => 500000,
            'investment_category' => 'normal',
            'return_type' => 'percentage',
            'return_percentage' => null,
            'fixed_return_amount' => 50000,
            'period_months' => 6,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['return_percentage', 'fixed_return_amount']);
    }

    public function test_update_normalizes_the_inactive_return_source(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $investment = $this->investment();

        $this->putJson("/api/v1/investments/{$investment->id}", [
            'investment_date' => '2026-08-21',
            'amount' => 600000,
            'investment_category' => 'normal',
            'return_type' => 'fixed',
            'return_percentage' => null,
            'fixed_return_amount' => 70000,
            'period_months' => 6,
        ])
            ->assertOk()
            ->assertJsonPath('data.return_percentage', null)
            ->assertJsonPath('data.fixed_return_amount', '70000.00')
            ->assertJsonPath('data.calculated_return_amount', 70000);

        $this->assertDatabaseHas('investments', [
            'id' => $investment->id,
            'return_percentage' => null,
            'fixed_return_amount' => 70000,
        ]);
    }

    public function test_payout_uses_investment_return_configuration(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $investment = $this->investment([
            'amount' => 500000,
            'return_type' => InvestmentReturnType::Percentage,
            'return_percentage' => 10,
            'fixed_return_amount' => null,
        ]);
        $customer = Customer::create([
            'code' => 'CUS-000001',
            'name' => 'Customer',
        ]);
        $estimate = Estimate::create([
            'code' => 'EST-000001',
            'customer_id' => $customer->id,
            'estimate_date' => '2026-08-21',
            'pickup' => 'Pickup',
            'destination' => 'Destination',
            'service_type' => 'goods',
            'estimated_sell' => 120000,
            'estimated_cost' => 100000,
            'estimated_profit' => 20000,
            'status' => 'accepted',
        ]);
        $job = TransportJob::create([
            'code' => 'JOB-000001',
            'estimate_id' => $estimate->id,
            'customer_id' => $customer->id,
            'job_date' => '2026-08-21',
            'status' => 'draft',
            'sell_price' => 120000,
            'cost_price' => 100000,
            'base_profit' => 20000,
            'extra_costs' => 0,
            'final_profit' => 20000,
        ]);
        InvestmentAllocation::create([
            'investment_id' => $investment->id,
            'transport_job_id' => $job->id,
            'amount' => 100000,
            'status' => 'active',
            'allocated_at' => now(),
        ]);

        $this->postJson("/api/v1/jobs/{$job->id}/profit-distributions", [
            'investment_id' => $investment->id,
        ])
            ->assertOk()
            ->assertJsonPath('data.profit_basis', '20000.00')
            ->assertJsonPath('data.profit_share_value', '10.0000')
            ->assertJsonPath('data.profit_amount', '50000.00');
    }

    private function investor(): Investor
    {
        return Investor::create([
            'investor_code' => 'INV-000001',
            'name' => 'Chloe Stark',
            'email' => 'chloe@example.com',
        ]);
    }

    private function investment(array $attributes = []): Investment
    {
        return Investment::create(array_merge([
            'investment_code' => 'INVEST-000001',
            'investor_id' => $this->investor()->id,
            'investment_date' => '2026-08-21',
            'amount' => 500000,
            'investment_category' => InvestmentCategory::Normal,
            'return_type' => InvestmentReturnType::Percentage,
            'return_percentage' => 3,
            'fixed_return_amount' => null,
            'period_months' => 6,
        ], $attributes));
    }
}
