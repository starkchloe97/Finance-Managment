<?php

namespace App\Services;

use App\Models\TransportJob;
use App\Models\Estimate;
use App\Models\TransportJobBudget ;
use App\Helpers\NumberGenerator;
use Illuminate\Support\Facades\DB;

class JobService
{
    public function convert(Estimate $estimate)
    {
        return DB::transaction(function () use ($estimate) {

            if ($estimate->job) {
                throw new \Exception('Job already exists.');
            }

            $TransportJob = TransportJob::create([
                'code' => NumberGenerator::generate('JOB', TransportJob::class),
                'estimate_id' => $estimate->id,
                'customer_id' => $estimate->customer_id,
                'job_date' => now(),
                'quoted_amount' => $estimate->total,
                'planned_cost' => 0,
                'actual_cost' => 0,
                'profit' => 0,
                'status' => 'draft'
            ]);

            foreach ($estimate->items as $item) {

                TransportJobBudget ::create([

                    'job_id' => $job->id,

                    'title' => $item->title,

                    'category' => $item->category,

                    'quantity' => $item->quantity,

                    'unit_cost' => 0,

                    'total' => 0,

                    'notes' => $item->notes

                ]);

            }

            return $job->load('customer','estimate','budgetItems');

        });
    }
}