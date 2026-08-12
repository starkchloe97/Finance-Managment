<?php

namespace App\Services;

use App\Models\TransportJob;
use App\Models\TransportJobExpense;

/**
 * Expenses are what the company actually spent on a job. Each one is recorded
 * as it happens, so the job's actual_cost and profit move with them.
 */
class TransportJobExpenseService
{
    public function add(TransportJob $job, array $data)
    {
        $expense = $job->expenses()->create($data);

        $job->recalculate();

        return $expense;
    }

    public function remove(TransportJobExpense $expense)
    {
        $job = $expense->transportJob;

        $expense->delete();

        $job->recalculate();
    }
}
