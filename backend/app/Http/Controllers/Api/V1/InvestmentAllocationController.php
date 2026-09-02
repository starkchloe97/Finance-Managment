<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\InvestmentAllocationRequest;
use App\Http\Resources\InvestmentAllocationResource;
use App\Models\Investment;
use App\Models\InvestmentAllocation;
use App\Models\TransportJob;
use App\Services\InvestmentAllocationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class InvestmentAllocationController extends Controller
{
    public function index(Investment $investment): AnonymousResourceCollection
    {
        return InvestmentAllocationResource::collection($investment->allocations()->with(['investment.investor', 'transportJob'])->latest()->paginate(15));
    }

    public function store(InvestmentAllocationRequest $request, Investment $investment, InvestmentAllocationService $service): InvestmentAllocationResource
    {
        return new InvestmentAllocationResource($service->allocate($investment, TransportJob::findOrFail($request->integer('transport_job_id')), (float) $request->input('amount'), $request->input('notes')));
    }

    public function destroy(InvestmentAllocation $allocation, InvestmentAllocationService $service): JsonResponse
    {
        $service->release($allocation);

        return response()->json(['message' => 'Allocation released successfully.']);
    }
}
