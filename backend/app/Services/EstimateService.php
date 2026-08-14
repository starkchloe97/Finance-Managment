<?php

namespace App\Services;

use App\Models\Estimate;
use App\Helpers\NumberGenerator;
use Illuminate\Support\Facades\DB;

/**
 * An estimate is the quote we send the customer — the selling price.
 *
 * Each line is what the customer pays for that item, so the estimate total is
 * simply the sum of its lines. What the job actually costs us is tracked
 * separately on the job (planned_cost from the budget, actual_cost from
 * expenses), and profit is the difference. No cost or margin belongs here.
 */
class EstimateService
{
    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            $estimatedCost = 0;
            $estimatedSell = 0;
            $estimatedProfit = 0;

            foreach ($data['items'] as $item) {
                $costTotal = $item['quantity'] * $item['cost_price'];
                $sellTotal = $item['quantity'] * $item['sell_price'];
                $profit = $sellTotal - $costTotal;

                $estimatedCost += $costTotal;
                $estimatedSell += $sellTotal;
                $estimatedProfit += $profit;
            }

            $estimate = Estimate::create([
                'code' => NumberGenerator::generate('EST', Estimate::class),
                'customer_id' => $data['customer_id'],
                'estimate_date' => $data['estimate_date'],
                'valid_until' => $data['valid_until'] ?? null,
                'pickup' => $data['pickup'],
                'destination' => $data['destination'],
                'service_type' => $data['service_type'],
                'estimated_cost' => $estimatedCost,
                'estimated_sell' => $estimatedSell,
                'estimated_profit' => $estimatedProfit,
                'status' => 'draft',
                'remarks' => $data['remarks'] ?? null,
            ]);

            foreach ($data['items'] as $item) {
                $costTotal = $item['quantity'] * $item['cost_price'];
                $sellTotal = $item['quantity'] * $item['sell_price'];
                $profit = $sellTotal - $costTotal;

                $estimate->items()->create([
                    'title' => $item['title'],
                    'category' => $item['category'],
                    'quantity' => $item['quantity'],
                    'cost_price' => $item['cost_price'],
                    'sell_price' => $item['sell_price'],
                    'cost_total' => $costTotal,
                    'sell_total' => $sellTotal,
                    'profit' => $profit,
                    'remarks' => $item['remarks'] ?? null,
                ]);
            }

            return $estimate->load('customer', 'items');
        });
    }
}
