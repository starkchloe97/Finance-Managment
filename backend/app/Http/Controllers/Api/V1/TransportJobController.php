<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\TransportJobResource;
use App\Models\Estimate;
use App\Models\TransportJob;
use App\Services\TransportJobService;

class TransportJobController extends Controller
{
    public function index()
    {
        return TransportJobResource::collection(
            TransportJob::with('customer')->latest()->paginate(10)
        );
    }

    public function show(TransportJob $job)
    {
        return new TransportJobResource(
            $job->load('customer', 'estimate', 'budgetItems', 'expenses')
        );
    }

    /**
     * The customer accepted the quote, so turn it into a job to work on.
     */
    public function convert(Estimate $estimate, TransportJobService $service)
    {
        return new TransportJobResource(
            $service->convert($estimate)
        );
    }
}
