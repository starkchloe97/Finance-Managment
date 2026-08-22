<?php

namespace Tests\Feature;

use App\Enums\InvestmentCategory;
use App\Enums\InvestmentReturnType;
use App\Enums\InvestmentStatus;
use App\Models\Investment;
use App\Models\Investor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InvestmentLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private function investment(array $attributes = []): Investment
    {
        $investor = Investor::create([
            'investor_code' => 'INV-000001',
            'name' => 'Chloe Stark',
            'email' => 'chloe@example.com',
        ]);

        return Investment::create(array_merge([
            'investment_code' => 'INVEST-000001',
            'investor_id' => $investor->id,
            'investment_date' => now()->subMonth()->toDateString(),
            'amount' => 500000,
            'investment_category' => InvestmentCategory::Normal,
            'return_type' => InvestmentReturnType::Percentage,
            'return_percentage' => 3,
            'fixed_return_amount' => null,
            'period_months' => 1,
            'deduction_amount' => 500,
        ], $attributes));
    }

    public function test_an_investment_cannot_mature_before_its_maturity_date(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $investment = $this->investment(['investment_date' => today()->toDateString()]);

        $this->postJson("/api/v1/investments/{$investment->id}/mature")
            ->assertStatus(422)
            ->assertJsonValidationErrors('status');

        $this->assertSame(InvestmentStatus::Active, $investment->fresh()->status);
    }

    public function test_only_lifecycle_endpoints_can_change_status(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $investment = $this->investment();

        $this->putJson("/api/v1/investments/{$investment->id}", [
            'investment_date' => $investment->investment_date->toDateString(),
            'amount' => 500000,
            'investment_category' => 'normal',
            'return_type' => 'percentage',
            'return_percentage' => 3,
            'fixed_return_amount' => null,
            'period_months' => 1,
            'status' => 'settled',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('status');

        $this->assertSame(InvestmentStatus::Active, $investment->fresh()->status);
    }

    public function test_matured_investments_can_be_settled_with_lifecycle_timestamps(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $investment = $this->investment();

        $this->postJson("/api/v1/investments/{$investment->id}/mature")
            ->assertOk()
            ->assertJsonPath('data.status', 'matured');

        $this->postJson("/api/v1/investments/{$investment->id}/settle")
            ->assertOk()
            ->assertJsonPath('data.status', 'settled')
            ->assertJsonPath('data.calculated_return_amount', 15000)
            ->assertJsonPath('data.expected_settlement_amount', 514500);

        $investment = $investment->fresh();

        $this->assertNotNull($investment->matured_at);
        $this->assertNotNull($investment->settled_at);
        $this->assertSame(InvestmentStatus::Settled, $investment->status);
    }

    public function test_an_investor_with_investments_cannot_be_deleted(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $investment = $this->investment();

        $this->deleteJson("/api/v1/investors/{$investment->investor_id}")
            ->assertStatus(422)
            ->assertJsonValidationErrors('investor');

        $this->assertDatabaseHas('investors', ['id' => $investment->investor_id]);
    }

    public function test_settled_investments_cannot_be_deleted(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $investment = $this->investment(['status' => InvestmentStatus::Settled]);

        $this->deleteJson("/api/v1/investments/{$investment->id}")
            ->assertStatus(422)
            ->assertJsonValidationErrors('investment');

        $this->assertDatabaseHas('investments', ['id' => $investment->id]);
    }
}
