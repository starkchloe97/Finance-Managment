<?php

namespace Tests\Feature;

use App\Enums\JobStatus;
use App\Models\Customer;
use App\Models\Estimate;
use App\Models\TransportJob;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DashboardReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_returns_explicit_period_and_financial_scopes(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $this->job(today()->subDays(2), JobStatus::Confirmed, 1000, 600, 50, 350);

        $response = $this->getJson('/api/v1/dashboard?period=this_month');

        $response->assertOk()
            ->assertJsonPath('meta.period', 'this_month')
            ->assertJsonPath('meta.scopes.kpis', 'selected_period')
            ->assertJsonPath('meta.scopes.current_pipeline', 'all_jobs')
            ->assertJsonPath('kpis.cost.value', 600)
            ->assertJsonPath('kpis.planned_cost.value', 600)
            ->assertJsonPath('kpis.actual_cost.value', 650)
            ->assertJsonPath('kpis.extra_costs.value', 50)
            ->assertJsonPath('kpis.profit.value', 350)
            ->assertJsonPath('kpis.profit_margin.value', 35)
            ->assertJsonPath('current_pipeline.confirmed', 1);
    }

    public function test_dashboard_accepts_a_valid_custom_range(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $from = today()->subDays(5);
        $to = today()->subDays(2);
        $this->job($from->copy()->addDay(), JobStatus::Assigned, 2000, 1000, 0, 1000);

        $this->getJson("/api/v1/dashboard?period=custom&from={$from->toDateString()}&to={$to->toDateString()}")
            ->assertOk()
            ->assertJsonPath('meta.period', 'custom')
            ->assertJsonPath('meta.from', $from->toDateString())
            ->assertJsonPath('meta.to', $to->toDateString())
            ->assertJsonPath('kpis.revenue.value', 2000);
    }

    public function test_dashboard_rejects_an_invalid_custom_range(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/v1/dashboard?period=custom&from=2026-08-20&to=2026-08-10')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['to']);
    }

    public function test_dashboard_compares_active_jobs_with_previous_active_jobs(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $this->job(today()->subDays(2), JobStatus::Confirmed, 1000, 500, 0, 500);
        $this->job(today()->subDays(1), JobStatus::Completed, 1000, 500, 0, 500);
        $this->job(today()->subMonth()->startOfMonth(), JobStatus::Confirmed, 1000, 500, 0, 500);
        $this->job(today()->subMonth()->addDay(), JobStatus::Completed, 1000, 500, 0, 500);

        $this->getJson('/api/v1/dashboard?period=this_month')
            ->assertOk()
            ->assertJsonPath('kpis.active_jobs.value', 1)
            ->assertJsonPath('kpis.active_jobs.previous', 1);
    }

    public function test_dashboard_returns_review_alerts_for_real_records(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $loss = $this->job(today(), JobStatus::InTransit, 1000, 500, 0, -100);
        $customer = $this->customer();
        $estimate = Estimate::create([
            'code' => 'EST-ALERT-000001',
            'customer_id' => $customer->id,
            'estimate_date' => today(),
            'valid_until' => today()->addDays(3),
            'pickup' => 'Karachi',
            'destination' => 'Lahore',
            'service_type' => 'goods',
            'status' => 'sent',
            'estimated_sell' => 1000,
        ]);

        $response = $this->getJson('/api/v1/dashboard?period=this_month');

        $response->assertOk()
            ->assertJsonFragment([
                'type' => 'job',
                'id' => $loss->id,
                'href' => "/jobs/{$loss->id}",
            ])
            ->assertJsonFragment([
                'type' => 'estimate',
                'id' => $estimate->id,
                'href' => "/estimates/{$estimate->id}",
            ]);
    }

    private function customer(): Customer
    {
        return Customer::create([
            'code' => 'CUS-'.uniqid(),
            'name' => 'Dashboard Customer',
        ]);
    }

    private function job($date, JobStatus $status, float $sell, float $cost, float $extra, float $profit): TransportJob
    {
        $customer = $this->customer();
        $estimate = Estimate::create([
            'code' => 'EST-'.uniqid(),
            'customer_id' => $customer->id,
            'estimate_date' => $date,
            'pickup' => 'Karachi',
            'destination' => 'Lahore',
            'service_type' => 'goods',
            'status' => 'accepted',
            'estimated_sell' => $sell,
        ]);

        return TransportJob::create([
            'code' => 'JOB-'.uniqid(),
            'estimate_id' => $estimate->id,
            'customer_id' => $customer->id,
            'job_date' => $date,
            'status' => $status,
            'sell_price' => $sell,
            'cost_price' => $cost,
            'base_profit' => $sell - $cost,
            'extra_costs' => $extra,
            'final_profit' => $profit,
        ]);
    }
}
