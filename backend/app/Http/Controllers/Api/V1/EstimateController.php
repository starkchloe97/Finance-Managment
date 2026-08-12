<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\EstimateRequest;
use App\Http\Resources\EstimateResource;
use App\Models\Estimate;
use App\Services\EstimateService;
use Illuminate\Http\Request;

class EstimateController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return EstimateResource::collection(
            Estimate::with('customer')->latest()->paginate(10)
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(
    EstimateRequest $request,
    EstimateService $service
    )
    {
        return new EstimateResource(

            $service->create(
                $request->validated()
            )

        );
    }
    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
