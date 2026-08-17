<?php

namespace App\Services;

use App\Models\TransportJob;
use App\Models\TransportJobExpense;

/**
 * Expenses are the unexpected costs that turn up once a job is under way —
 * a breakdown, a delay, an extra permit. They are not part of the agreed cost
 * price, so each one is added to the job's extra_costs and comes off the
 * profit the job was taken on.
 */
class TransportJobExpenseService
{
    public function add(TransportJob $job, array $data)
    {
        $expense = $job->expenses()->create($data);

        $job->recalculate();

        return $expense;
    }

    public function update(TransportJobExpense $expense, array $data)
    {
        $expense->update($data);

        $job = $expense->transportJob;
        $job->recalculate();

        return $expense->fresh();
    }

    public function remove(TransportJobExpense $expense)
    {
        $job = $expense->transportJob;

        $expense->delete();

        $job->recalculate();
    }
}
