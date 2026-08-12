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

            $total = 0;

            foreach ($data['items'] as $item) {
                $total += $item['quantity'] * $item['unit_price'];
            }

            $estimate = Estimate::create([
                'code' => NumberGenerator::generate('EST', Estimate::class),
                'customer_id' => $data['customer_id'],
                'estimate_date' => $data['estimate_date'],
                'valid_until' => $data['valid_until'] ?? null,
                'pickup' => $data['pickup'],
                'destination' => $data['destination'],
                'service_type' => $data['service_type'],
                'total' => $total,
                'status' => 'draft',
                'remarks' => $data['remarks'] ?? null,
            ]);

            foreach ($data['items'] as $item) {
                $estimate->items()->create([
                    'title' => $item['title'],
                    'category' => $item['category'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total' => $item['quantity'] * $item['unit_price'],
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            return $estimate->load('customer', 'items');

        });
    }
}
