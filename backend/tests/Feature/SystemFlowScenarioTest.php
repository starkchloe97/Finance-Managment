<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Services\EstimateService;
use App\Services\TransportJobExpenseService;
use App\Services\TransportJobService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * End-to-end walkthrough of the core operational flow, mirroring the
 * scenario in docs/System_flow.txt section 15:
 *
 *   Customer → Estimate → Transport Job → Expenses → Profit
 *
 * The estimate captures both sides of the deal (what it costs us and what we
 * charge the customer), so the planned profit is known at quote time. On
 * conversion the job takes a fixed copy of those figures and never lets a later
 * estimate edit rewrite it. Unexpected costs found during the job are recorded
 * as expenses and come straight off that profit; the agreed sell price is
 * sacred and never moves.
 */
class SystemFlowScenarioTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Build the exact section-15 customer and estimate:
     * three items totalling 120,000 sell / 80,000 cost → 40,000 planned profit.
     */
    private function customer(): Customer
    {
        return Customer::create([
            'code' => 'CUS-000001',
            'name' => 'ABC Construction',
            'phone' => '0300-0000000',
            'email' => 'john@example.com',
            'company' => 'ABC Construction',
        ]);
    }

    private function estimate(Customer $customer)
    {
        return app(EstimateService::class)->create([
            'customer_id' => $customer->id,
            'estimate_date' => '2026-08-20',
            'pickup' => 'Karachi',
            'destination' => 'Lahore',
            'service_type' => 'goods',
            'items' => [
                // Each line carries a cost to us and a price to the customer.
                ['title' => 'Transportation', 'category' => 'Transport', 'quantity' => 1, 'cost_price' => 45000, 'sell_price' => 70000],
                ['title' => 'Machinery',      'category' => 'Machinery', 'quantity' => 1, 'cost_price' => 20000, 'sell_price' => 30000],
                ['title' => 'Labor',          'category' => 'Labor',     'quantity' => 1, 'cost_price' => 15000, 'sell_price' => 20000],
            ],
        ]);
    }

    public function test_customer_holds_the_estimate_and_job(): void
    {
        $customer = $this->customer();
        $estimate = $this->estimate($customer);
        $job = app(TransportJobService::class)->convert($estimate);

        $this->assertSame('ABC Construction', $customer->fresh()->name);

        $this->assertSame($customer->id, $estimate->customer_id);
        $this->assertSame($customer->id, $job->customer_id);

        $this->assertCount(1, $customer->estimates);
        $this->assertCount(1, $customer->jobs);
    }

    public function test_estimate_totals_cost_sell_and_profit(): void
    {
        $estimate = $this->estimate($this->customer());

        // The section-15 "quoted total 120,000" and "planned cost 80,000".
        $this->assertSame(120000.0, (float) $estimate->estimated_sell);
        $this->assertSame(80000.0, (float) $estimate->estimated_cost);
        $this->assertSame(40000.0, (float) $estimate->estimated_profit);

        $this->assertCount(3, $estimate->items);
        $this->assertStringStartsWith('EST-', $estimate->code);
        $this->assertSame('draft', $estimate->status);
    }

    public function test_conversion_copies_the_quote_into_the_job(): void
    {
        $customer = $this->customer();
        $estimate = $this->estimate($customer);

        $job = app(TransportJobService::class)->convert($estimate);

        $this->assertNotNull($job->code);
        $this->assertSame($estimate->id, $job->estimate_id);
        $this->assertSame($customer->id, $job->customer_id);
        $this->assertSame(120000.0, (float) $job->sell_price);
        $this->assertSame(80000.0, (float) $job->cost_price);
        $this->assertSame(40000.0, (float) $job->base_profit);
        $this->assertSame(0.0, (float) $job->extra_costs);
        $this->assertSame(40000.0, (float) $job->final_profit);
        $this->assertSame('draft', $job->status->value);

        // The estimate is recorded as accepted after conversion.
        $this->assertSame('accepted', $estimate->fresh()->status);
    }

    public function test_the_job_keeps_its_own_copy_of_the_figures(): void
    {
        $customer = $this->customer();
        $estimate = $this->estimate($customer);
        $job = app(TransportJobService::class)->convert($estimate);

        // A later edit to the estimate must not silently rewrite the job.
        app(EstimateService::class)->update($estimate, [
            'customer_id' => $estimate->customer_id,
            'estimate_date' => '2026-08-20',
            'pickup' => 'Karachi',
            'destination' => 'Lahore',
            'service_type' => 'goods',
            'items' => [
                ['title' => 'Transportation', 'category' => 'Transport', 'quantity' => 1, 'cost_price' => 1, 'sell_price' => 1],
            ],
        ]);

        $job->refresh();

        $this->assertSame(120000.0, (float) $job->sell_price);
        $this->assertSame(80000.0, (float) $job->cost_price);
        $this->assertSame(40000.0, (float) $job->base_profit);
    }

    public function test_unexpected_costs_reduce_the_profit_but_never_the_sell_price(): void
    {
        $job = app(TransportJobService::class)->convert($this->estimate($this->customer()));
        $service = app(TransportJobExpenseService::class);

        // "Budget" track entered as actual expenses, equal to planned cost.
        foreach ([
            ['title' => 'Transportation', 'category' => 'fuel',            'amount' => 45000],
            ['title' => 'Machinery',      'category' => 'miscellaneous',   'amount' => 20000],
            ['title' => 'Labor',          'category' => 'accommodation',   'amount' => 15000],
        ] as $data) {
            $service->add($job, $data + ['expense_date' => '2026-08-21']);
        }

        $job->refresh();

        $this->assertSame(80000.0, (float) $job->extra_costs);

        // The sacred invariants hold: sell price fixed, profit erodes.
        $this->assertSame(120000.0, (float) $job->sell_price);
        $this->assertSame(40000.0, (float) $job->base_profit);
        $this->assertSame(-40000.0, (float) $job->final_profit);

        // The customer price must not change.
        $this->assertSame(120000.0, (float) $job->sell_price);
    }

    public function test_add_edit_remove_expense_walk_profit_through_every_step(): void
    {
        $job = app(TransportJobService::class)->convert($this->estimate($this->customer()));
        $service = app(TransportJobExpenseService::class);

        // Start clean at the agreed position.
        $this->assertSame(40000.0, (float) $job->fresh()->final_profit);

        // Add one unexpected cost: 40,000 - 12,000 = 28,000.
        $expense = $service->add($job, [
            'title' => 'Fuel',
            'category' => 'fuel',
            'amount' => 12000,
            'expense_date' => '2026-08-21',
        ]);
        $this->assertSame(28000.0, (float) $job->fresh()->final_profit);

        // Edit it up: 28,000 - 3,000 = 25,000.
        $service->update($expense, ['amount' => 15000, 'expense_date' => '2026-08-21']);
        $this->assertSame(25000.0, (float) $job->fresh()->final_profit);

        // Remove it: back to the agreed 40,000.
        $service->remove($expense);
        $this->assertSame(40000.0, (float) $job->fresh()->final_profit);

        // Never once did the customer price move.
        $this->assertSame(120000.0, (float) $job->fresh()->sell_price);
        $this->assertSame(80000.0, (float) $job->fresh()->cost_price);
    }

    public function test_cost_overruns_turn_a_profit_into_a_real_loss(): void
    {
        $job = app(TransportJobService::class)->convert($this->estimate($this->customer()));
        $service = app(TransportJobExpenseService::class);

        $service->add($job, [
            'title' => 'Engine replacement',
            'category' => 'repair',
            'amount' => 50000,
            'expense_date' => '2026-08-21',
        ]);

        // 40,000 planned profit - 50,000 unexpected = -10,000, shown as a loss.
        $this->assertSame(-10000.0, (float) $job->fresh()->final_profit);
        $this->assertSame(120000.0, (float) $job->fresh()->sell_price);
    }

    public function test_estimate_lifecycle_can_be_walked_to_accepted_then_converted(): void
    {
        $customer = $this->customer();
        $estimate = $this->estimate($customer);

        // Scenario A: draft → sent → accepted, each set directly on the quote.
        app(EstimateService::class)->update($estimate->fresh(), self::payload($estimate, 'sent'));
        $this->assertSame('sent', $estimate->fresh()->status);

        app(EstimateService::class)->update($estimate->fresh(), self::payload($estimate, 'accepted'));
        $this->assertSame('accepted', $estimate->fresh()->status);

        app(TransportJobService::class)->convert($estimate->fresh());
        $this->assertSame('accepted', $estimate->fresh()->status);
        $this->assertNotNull($estimate->fresh()->transportJob);
    }

    public function test_a_converted_estimate_becomes_read_only(): void
    {
        $customer = $this->customer();
        $estimate = $this->estimate($customer);
        $job = app(TransportJobService::class)->convert($estimate);

        try {
            app(EstimateService::class)->update($estimate->fresh(), self::payload($estimate, 'draft'));
            $this->fail('Editing a converted estimate should be refused.');
        } catch (ValidationException $e) {
            $this->assertSame('This estimate has already been converted and cannot be edited.', $e->errors()['estimate'][0]);
        }

        // The job keeps the figures it was given.
        $this->assertSame(120000.0, (float) $job->fresh()->sell_price);
        $this->assertSame(40000.0, (float) $job->fresh()->base_profit);
    }

    public function test_converting_the_same_estimate_twice_is_refused(): void
    {
        $estimate = $this->estimate($this->customer());

        app(TransportJobService::class)->convert($estimate->fresh());

        try {
            app(TransportJobService::class)->convert($estimate->fresh());
            $this->fail('Double conversion should be refused.');
        } catch (ValidationException $e) {
            $this->assertSame('This estimate has already been converted to a job.', $e->errors()['estimate'][0]);
        }

        // Only one job exists.
        $this->assertSame(1, $estimate->fresh()->transportJob()->count());
    }

    private static function payload($estimate, string $status): array
    {
        return [
            'customer_id' => $estimate->customer_id,
            'estimate_date' => $estimate->estimate_date->toDateString(),
            'pickup' => $estimate->pickup,
            'destination' => $estimate->destination,
            'service_type' => $estimate->service_type,
            'status' => $status,
            'items' => $estimate->items->map(fn ($item) => [
                'title' => $item->title,
                'category' => $item->category,
                'quantity' => $item->quantity,
                'cost_price' => $item->cost_price,
                'sell_price' => $item->sell_price,
            ])->all(),
        ];
    }
}
