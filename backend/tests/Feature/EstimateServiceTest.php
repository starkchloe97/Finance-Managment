<?php

namespace Tests\Feature;

use App\Models\Customer;
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

        $this->assertCount(2, $estimate->items);

        $this->assertSame(100.0, (float) $estimate->items[0]->cost_total);
        $this->assertSame(200.0, (float) $estimate->items[0]->sell_total);
        $this->assertSame(100.0, (float) $estimate->items[0]->profit);

        $this->assertSame(25.0, (float) $estimate->items[1]->cost_total);
        $this->assertSame(80.0, (float) $estimate->items[1]->sell_total);
        $this->assertSame(55.0, (float) $estimate->items[1]->profit);
    }
}
