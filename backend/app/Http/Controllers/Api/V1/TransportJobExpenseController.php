<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExpenseRequest;
use App\Http\Resources\TransportJobResource;
use App\Models\TransportJob;
use App\Models\TransportJobExpense;
use App\Services\TransportJobExpenseService;

/**
 * Every action answers with the whole job rather than the expense that
 * changed, because changing an expense changes the job's totals — the caller
 * needs the new figures, and this way it never has to work them out or ask
 * again.
 */
class TransportJobExpenseController extends Controller
{
    public function __construct(
        private TransportJobExpenseService $service
    ) {}

    public function store(ExpenseRequest $request, TransportJob $job)
    {
        $this->service->add($job, $request->validated());

        return $this->job($job);
    }

    public function update(ExpenseRequest $request, TransportJob $job, TransportJobExpense $expense)
    {
        $this->service->update($expense, $request->validated());

        return $this->job($job);
    }

    public function destroy(TransportJob $job, TransportJobExpense $expense)
    {
        $this->service->remove($expense);

        return $this->job($job);
    }

    private function job(TransportJob $job): TransportJobResource
    {
        return new TransportJobResource(
            $job->fresh(['customer', 'estimate.items', 'expenses'])
        );
    }
}
