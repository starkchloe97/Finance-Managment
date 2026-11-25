<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Estimate;
use App\Services\EstimateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EstimateServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_calculates_cost_sell_and_profit_for_each_item_and_estimate_totals(): void
    {
        $customer = Customer::create([
            'code' => 'CUS-000001',
            'name' => 'Acme Logistics',
            'phone' => '555-0100',
            'email' => 'acme@example.com',
            'company' => 'Acme',
            'address' => '123 Main St',
        ]);

        $estimate = app(EstimateService::class)->create([
            'customer_id' => $customer->id,
            'estimate_date' => '2026-08-14',
            'pickup' => 'Cape Town',
            'destination' => 'Johannesburg',
            'service_type' => 'goods',
            'tonnage' => 12.5,
            'remarks' => 'Urgent move',
            'items' => [
                [
                    'title' => 'Container freight',
                    'category' => 'Freight',
                    'quantity' => 2,
                    'cost_price' => 50,
                    'sell_price' => 100,
                    'remarks' => 'First item',
                ],
                [
                    'title' => 'Handling',
                    'category' => 'Labor',
                    'quantity' => 1,
                    'cost_price' => 25,
                    'sell_price' => 80,
                    'remarks' => 'Second item',
                ],
            ],
        ]);

        $this->assertSame(125.0, (float) $estimate->estimated_cost);
        $this->assertSame(280.0, (float) $estimate->estimated_sell);
        $this->assertSame(155.0, (float) $estimate->estimated_profit);
        $this->assertSame('12.500', $estimate->tonnage);

        $this->assertCount(2, $estimate->items);

        $this->assertSame(100.0, (float) $estimate->items[0]->cost_total);
        $this->assertSame(200.0, (float) $estimate->items[0]->sell_total);
        $this->assertSame(100.0, (float) $estimate->items[0]->profit);

        $this->assertSame(25.0, (float) $estimate->items[1]->cost_total);
        $this->assertSame(80.0, (float) $estimate->items[1]->sell_total);
        $this->assertSame(55.0, (float) $estimate->items[1]->profit);
    }

    public function test_repair_estimate_calculates_parts_labor_additional_charges_and_tax(): void
    {
        $customer = Customer::create([
            'code' => 'CUS-000001',
            'name' => 'Acme Logistics',
            'phone' => '555-0100',
            'email' => 'acme@example.com',
            'company' => 'Acme',
            'address' => '123 Main St',
        ]);

        $estimate = app(EstimateService::class)->create([
            'customer_id' => $customer->id,
            'estimate_type' => 'repair',
            'estimate_date' => '2026-08-14',
            'service_type' => 'vehicle',
            'details' => [
                'vehicle_make' => 'Toyota',
                'workshop_charges' => 10,
                'towing_charges' => 20,
                'other_expenses' => 5,
            ],
            'tax_rate' => 10,
            'terms' => 'Customer approval is required before work starts.',
            'payment_terms' => 'Due on completion',
            'items' => [[
                'title' => 'Brake system',
                'category' => 'Repair',
                'quantity' => 1,
                'parts_cost' => 100,
                'labor_cost' => 50,
                'cost_price' => 150,
                'sell_price' => 150,
            ]],
        ]);

        $this->assertSame(185.0, (float) $estimate->estimated_cost);
        $this->assertSame(185.0, (float) $estimate->estimated_sell);
        $this->assertSame(18.5, (float) $estimate->tax_amount);
        $this->assertSame(203.5, (float) $estimate->grand_total);
        $this->assertSame(100.0, (float) $estimate->items[0]->parts_cost);
        $this->assertSame(50.0, (float) $estimate->items[0]->labor_cost);
        $this->assertSame('repair', $estimate->estimate_type);
        $this->assertSame('Due on completion', $estimate->payment_terms);
    }

    public function test_accepting_an_estimate_automatically_creates_a_job_using_the_grand_total(): void
    {
        $customer = Customer::create([
            'code' => 'CUS-000001',
            'name' => 'Acme Logistics',
            'phone' => '555-0100',
            'email' => 'acme@example.com',
            'company' => 'Acme',
            'address' => '123 Main St',
        ]);

        $estimate = app(EstimateService::class)->create([
            'customer_id' => $customer->id,
            'estimate_date' => '2026-08-14',
            'pickup' => 'Karachi',
            'destination' => 'Lahore',
            'service_type' => 'goods',
            'tax_rate' => 17,
            'items' => [[
                'title' => 'Transport service',
                'category' => 'Transport',
                'quantity' => 1,
                'cost_price' => 100,
                'sell_price' => 200,
            ]],
        ]);

        $updated = app(EstimateService::class)->update($estimate, [
            'customer_id' => $customer->id,
            'estimate_date' => '2026-08-14',
            'pickup' => 'Karachi',
            'destination' => 'Lahore',
            'service_type' => 'goods',
            'tax_rate' => 17,
            'status' => 'accepted',
            'items' => [[
                'title' => 'Transport service',
                'category' => 'Transport',
                'quantity' => 1,
                'cost_price' => 100,
                'sell_price' => 200,
            ]],
        ]);

        $this->assertSame('accepted', $updated->status);
        $this->assertNotNull($updated->transportJob);
        $this->assertSame(200.0, (float) $updated->transportJob->sell_price);
        $this->assertSame(234.0, (float) $updated->grand_total);
        $this->assertSame(1, Estimate::findOrFail($estimate->id)->transportJob()->count());
    }
}
