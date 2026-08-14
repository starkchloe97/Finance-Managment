<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\TransportJob;
use App\Services\EstimateService;
use App\Services\TransportJobExpenseService;
use App\Services\TransportJobService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransportJobProfitTest extends TestCase
{
    use RefreshDatabase;

    private function job(): TransportJob
    {
        $customer = Customer::create([
            'code' => 'CUS-000001',
            'name' => 'Acme Logistics',
        ]);

        // Cost 61,500 / sell 70,000 -> base profit 8,500
        $estimate = app(EstimateService::class)->create([
            'customer_id' => $customer->id,
            'estimate_date' => '2026-08-14',
            'pickup' => 'Karachi',
            'destination' => 'Lahore',
            'service_type' => 'goods',
            'items' => [
                ['title' => 'Transportation', 'category' => 'Transport', 'quantity' => 1, 'cost_price' => 45000, 'sell_price' => 50000],
                ['title' => 'Labor', 'category' => 'Labor', 'quantity' => 1, 'cost_price' => 8000, 'sell_price' => 10000],
                ['title' => 'Crane', 'category' => 'Machinery', 'quantity' => 1, 'cost_price' => 6500, 'sell_price' => 8000],
                ['title' => 'Toll', 'category' => 'Other', 'quantity' => 1, 'cost_price' => 2000, 'sell_price' => 2000],
            ],
        ]);

        return app(TransportJobService::class)->convert($estimate);
    }

    public function test_converting_copies_cost_sell_and_base_profit_from_the_estimate(): void
    {
        $job = $this->job();

        $this->assertSame(70000.0, (float) $job->sell_price);
        $this->assertSame(61500.0, (float) $job->cost_price);
        $this->assertSame(8500.0, (float) $job->base_profit);
        $this->assertSame(0.0, (float) $job->extra_costs);
        $this->assertSame(8500.0, (float) $job->final_profit);
    }

    public function test_an_unexpected_cost_is_deducted_from_the_base_profit(): void
    {
        $job = $this->job();

        app(TransportJobExpenseService::class)->add($job, [
            'title' => 'Truck repair',
            'category' => 'Breakdown',
            'amount' => 5000,
            'expense_date' => '2026-08-15',
        ]);

        $job->refresh();

        $this->assertSame(5000.0, (float) $job->extra_costs);
        $this->assertSame(3500.0, (float) $job->final_profit);

        // The agreed figures must not move.
        $this->assertSame(70000.0, (float) $job->sell_price);
        $this->assertSame(61500.0, (float) $job->cost_price);
        $this->assertSame(8500.0, (float) $job->base_profit);
    }

    public function test_multiple_expenses_accumulate(): void
    {
        $job = $this->job();
        $service = app(TransportJobExpenseService::class);

        foreach ([1000, 2000, 500] as $amount) {
            $service->add($job, [
                'title' => 'Extra',
                'category' => 'Other',
                'amount' => $amount,
                'expense_date' => '2026-08-15',
            ]);
        }

        $job->refresh();

        $this->assertSame(3500.0, (float) $job->extra_costs);
        $this->assertSame(5000.0, (float) $job->final_profit);
    }

    public function test_expenses_beyond_the_base_profit_turn_the_job_into_a_loss(): void
    {
        $job = $this->job();

        app(TransportJobExpenseService::class)->add($job, [
            'title' => 'Engine replacement',
            'category' => 'Breakdown',
            'amount' => 20000,
            'expense_date' => '2026-08-15',
        ]);

        $job->refresh();

        // 8,500 base profit - 20,000 unexpected = -11,500. The loss is recorded
        // as a negative number rather than clamped to zero.
        $this->assertSame(-11500.0, (float) $job->final_profit);
    }

    public function test_removing_an_expense_restores_the_profit(): void
    {
        $job = $this->job();
        $service = app(TransportJobExpenseService::class);

        $expense = $service->add($job, [
            'title' => 'Truck repair',
            'category' => 'Breakdown',
            'amount' => 5000,
            'expense_date' => '2026-08-15',
        ]);

        $service->remove($expense);

        $job->refresh();

        $this->assertSame(0.0, (float) $job->extra_costs);
        $this->assertSame(8500.0, (float) $job->final_profit);
    }
}
