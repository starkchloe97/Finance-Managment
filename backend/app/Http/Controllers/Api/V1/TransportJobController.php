<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateJobNotesRequest;
use App\Http\Requests\UpdateJobStatusRequest;
use App\Http\Resources\TransportJobResource;
use App\Models\Estimate;
use App\Models\TransportJob;
use App\Services\TransportJobService;
use Illuminate\Http\Request;

class TransportJobController extends Controller
{
    public function index(Request $request)
    {
        $query = TransportJob::with('customer');

        if ($search = $request->input('search')) {
            $query->where(function ($grouped) use ($search) {
                $grouped->where('code', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($customer) use ($search) {
                        $customer->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($customerId = $request->input('customer_id')) {
            $query->where('customer_id', $customerId);
        }

        $perPage = min(max((int) $request->input('per_page', 10), 1), 100);

        return TransportJobResource::collection(
            $query->latest()->paginate($perPage)
        );
    }

    public function show(TransportJob $job)
    {
        return new TransportJobResource(
            $job->load('customer', 'estimate.items', 'expenses', 'allocations.investment.investor', 'profitDistributions.investor', 'financialAdjustments.user')
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
