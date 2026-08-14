<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreExpenseRequest;
use App\Http\Resources\TransportJobResource;
use App\Models\TransportJob;
use App\Models\TransportJobExpense;
use App\Services\TransportJobExpenseService;

class TransportJobExpenseController extends Controller
{
    public function __construct(
        private TransportJobExpenseService $service
    ){}

    public function store(StoreExpenseRequest $request, TransportJob $job)
    {
        $this->service->add($job, $request->validated());

        return new TransportJobResource(
            $job->fresh(['customer', 'estimate.items', 'expenses'])
        );
    }

    public function destroy(TransportJobExpense $expense)
    {
        $job = $expense->transportJob;

        $this->service->remove($expense);

        return new TransportJobResource(
            $job->fresh(['customer', 'estimate.items', 'expenses'])
        );
    }
}
