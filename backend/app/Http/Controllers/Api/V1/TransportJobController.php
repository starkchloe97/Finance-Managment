<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateJobNotesRequest;
use App\Http\Requests\UpdateJobStatusRequest;
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
            $job->load('customer', 'estimate.items', 'expenses')
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

    /**
     * Move the job on to the next stage of the work.
     */
    public function updateStatus(
        UpdateJobStatusRequest $request,
        TransportJob $job,
        TransportJobService $service
    ) {
        return new TransportJobResource(
            $service->changeStatus($job, $request->validated('status'))
        );
    }

    /**
     * Working notes for the people running the job — internal only.
     */
    public function updateNotes(
        UpdateJobNotesRequest $request,
        TransportJob $job,
        TransportJobService $service
    ) {
        return new TransportJobResource(
            $service->updateNotes($job, $request->validated('internal_notes'))
        );
    }
}
