<?php

namespace App\Services;

use App\Models\Estimate;
use App\Helpers\NumberGenerator;
use Illuminate\Support\Facades\DB;

/**
 * An estimate captures both sides of a job in one step: what each line costs
 * us and what we charge the customer for it. Profit is therefore known as soon
 * as the quote is built, rather than being worked out later by comparing
 * separate documents.
 *
 * Only the sell figures are ever shown to the customer; cost and profit are
 * internal.
 */
class EstimateService
{
    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {

            $lines = array_map($this->buildLine(...), $data['items']);

            $estimate = Estimate::create([
                'code' => NumberGenerator::generate('EST', Estimate::class),
                'customer_id' => $data['customer_id'],
                'estimate_date' => $data['estimate_date'],
                'valid_until' => $data['valid_until'] ?? null,
                'pickup' => $data['pickup'],
                'destination' => $data['destination'],
                'service_type' => $data['service_type'],
                'estimated_cost' => array_sum(array_column($lines, 'cost_total')),
                'estimated_sell' => array_sum(array_column($lines, 'sell_total')),
                'estimated_profit' => array_sum(array_column($lines, 'profit')),
                'status' => 'draft',
                'remarks' => $data['remarks'] ?? null,
            ]);

            $estimate->items()->createMany($lines);

            return $estimate->load('customer', 'items');

        });
    }

    /**
     * Price one line. The estimate totals are just the sum of these, so the
     * arithmetic only lives here.
     */
    private function buildLine(array $item): array
    {
        $costTotal = $item['quantity'] * $item['cost_price'];
        $sellTotal = $item['quantity'] * $item['sell_price'];

        return [
            'title' => $item['title'],
            'category' => $item['category'],
            'quantity' => $item['quantity'],
            'cost_price' => $item['cost_price'],
            'sell_price' => $item['sell_price'],
            'cost_total' => $costTotal,
            'sell_total' => $sellTotal,
            'profit' => $sellTotal - $costTotal,
            'remarks' => $item['remarks'] ?? null,
        ];
    }
}
