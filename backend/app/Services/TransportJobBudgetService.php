<?php

namespace App\Services;

use App\Models\TransportJob;
use Illuminate\Support\Facades\DB;

/**
 * The budget is what the company expects to spend on a job — never shown to
 * the customer. Saving replaces every line, so the request always carries the
 * full list.
 */
class TransportJobBudgetService
{
    public function update(TransportJob $job, array $items)
    {
        DB::transaction(function () use ($job, $items) {

            $job->budgetItems()->delete();

            foreach ($items as $item) {

                $job->budgetItems()->create([
                    'title' => $item['title'],
                    'category' => $item['category'],
                    'quantity' => $item['quantity'],
                    'unit_cost' => $item['unit_cost'],
                    'total' => $item['quantity'] * $item['unit_cost'],
                    'notes' => $item['notes'] ?? null,
                ]);

            }

            $job->recalculate();

        });

        return $job->fresh('budgetItems');
    }
}
