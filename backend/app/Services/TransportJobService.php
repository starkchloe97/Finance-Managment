<?php

namespace App\Services;

use App\Models\TransportJob;
use App\Models\Estimate;
use App\Models\TransportJobBudget;
use App\Helpers\NumberGenerator;
use Illuminate\Support\Facades\DB;

/**
 * Turns an accepted estimate into a job.
 *
 * The estimate total carries over as quoted_amount — the price promised to the
 * customer, which never changes afterwards. The budget lines start at zero cost
 * because they are the company's expected spend, which the operations team
 * fills in next; only their titles are copied across as a starting checklist.
 */
class TransportJobService
{
    public function convert(Estimate $estimate)
    {
        return DB::transaction(function () use ($estimate) {

            if ($estimate->transportJob) {
                throw new \Exception('Job already exists.');
            }

            $estimate->update(['status' => 'accepted']);

            $quotedAmount = $estimate->estimated_sell ?? $estimate->total ?? 0;

            $job = TransportJob::create([
                'code' => NumberGenerator::generate('JOB', TransportJob::class),
                'estimate_id' => $estimate->id,
                'customer_id' => $estimate->customer_id,
                'job_date' => now(),
                'quoted_amount' => $quotedAmount,
                'planned_cost' => 0,
                'actual_cost' => 0,
                'profit' => 0,
                'status' => 'draft'
            ]);

            foreach ($estimate->items as $item) {

                TransportJobBudget::create([

                    'job_id' => $job->id,

                    'title' => $item->title,

                    'category' => $item->category,

                    'quantity' => $item->quantity,

                    'unit_cost' => 0,

                    'total' => 0,

                    'notes' => $item->notes

                ]);

            }

            return $job->load('customer', 'estimate', 'budgetItems');

        });
    }
}
