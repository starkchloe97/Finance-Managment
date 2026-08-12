<?php

namespace App\Services;

use App\Models\TransportJob;
use Illuminate\Support\Facades\DB;

class JobBudgetService
{
    public function update(TransportJob $job, array $items)
    {
        DB::transaction(function () use ($job, $items) {

            $job->budgetItems()->delete();

            $plannedCost = 0;

            foreach ($items as $item) {

                $total = $item['quantity'] * $item['unit_cost'];

                $plannedCost += $total;

                $job->budgetItems()->create([

                    'title' => $item['title'],

                    'category' => $item['category'],

                    'quantity' => $item['quantity'],

                    'unit_cost' => $item['unit_cost'],

                    'total' => $total,

                    'notes' => $item['notes'] ?? null

                ]);
            }

            $job->update([

                'planned_cost' => $plannedCost,

                'profit' => $job->quoted_amount - $plannedCost

            ]);
        });

        return $job->fresh('budgetItems');
    }
}