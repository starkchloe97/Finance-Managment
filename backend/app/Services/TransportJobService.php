<?php

namespace App\Services;

use App\Models\TransportJob;
use App\Models\Estimate;
use App\Helpers\NumberGenerator;
use Illuminate\Support\Facades\DB;

/**
 * Turns an accepted estimate into a job.
 *
 * The estimate already holds both sides of the deal, so the job simply takes a
 * copy of them: what we charge, what we expect it to cost, and the profit
 * between the two. They are copied rather than read through the relation so a
 * later edit to the estimate cannot quietly rewrite a job already under way.
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

            $job = TransportJob::create([
                'code' => NumberGenerator::generate('JOB', TransportJob::class),
                'estimate_id' => $estimate->id,
                'customer_id' => $estimate->customer_id,
                'job_date' => now(),
                'sell_price' => $estimate->estimated_sell,
                'cost_price' => $estimate->estimated_cost,
                'base_profit' => $estimate->estimated_profit,
                'extra_costs' => 0,
                'final_profit' => $estimate->estimated_profit,
                'status' => 'draft',
            ]);

            return $job->load('customer', 'estimate', 'expenses');

        });
    }
}
