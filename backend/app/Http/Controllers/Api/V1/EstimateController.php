<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\EstimateRequest;
use App\Http\Resources\EstimateResource;
use App\Models\Estimate;
use App\Services\EstimateService;

class EstimateController extends Controller
{
    public function index()
    {
        return EstimateResource::collection(
            Estimate::with('customer', 'transportJob')->latest()->paginate(10)
        );
    }

    public function store(EstimateRequest $request, EstimateService $service)
    {
        return new EstimateResource(
            $service->create($request->validated())
        );
    }
}
