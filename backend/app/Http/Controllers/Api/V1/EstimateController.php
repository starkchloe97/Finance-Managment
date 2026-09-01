<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\EstimateRequest;
use App\Http\Requests\UpdateEstimateRequest;
use App\Http\Resources\EstimateResource;
use App\Models\Estimate;
use App\Services\EstimateService;
use Illuminate\Http\Request;

class EstimateController extends Controller
{
    public function index(Request $request)
    {
        $query = Estimate::with('customer', 'transportJob');

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($grouped) use ($search) {
                $grouped->where('code', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($customer) use ($search) {
                        $customer->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($from = $request->input('date_from')) {
            $query->whereDate('estimate_date', '>=', $from);
        }

        if ($to = $request->input('date_to')) {
            $query->whereDate('estimate_date', '<=', $to);
        }

        $perPage = min(max((int) $request->input('per_page', 10), 1), 100);

        return EstimateResource::collection(
            $query->latest()->paginate($perPage)
        );
    }

    public function store(EstimateRequest $request, EstimateService $service)
    {
        return new EstimateResource(
            $service->create($request->validated())
        );
    }

    public function show(Estimate $estimate)
    {
        return new EstimateResource(
            $estimate->load('customer', 'items.vehicles.asset', 'transportJob')
        );
    }

    /**
     * Edit an estimate that has not been converted yet: the customer-facing
     * quote fields, line items, and status. A converted estimate is read-only
     * — the job carries its own copy of the figures.
     */
    public function update(UpdateEstimateRequest $request, Estimate $estimate, EstimateService $service)
    {
        return new EstimateResource(
            $service->update($estimate, $request->validated())
        );
    }
}
