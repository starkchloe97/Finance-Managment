<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\FinancialAdjustmentRequest;
use App\Http\Resources\FinancialAdjustmentResource;
use App\Models\TransportJob;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FinancialAdjustmentController extends Controller
{
    public function index(TransportJob $job): AnonymousResourceCollection
    {
        return FinancialAdjustmentResource::collection(
            $job->financialAdjustments()->with('user')->latest('created_at')->paginate(15)
        );
    }

    public function store(
        FinancialAdjustmentRequest $request,
        TransportJob $job
    ): FinancialAdjustmentResource {
        $adjustment = $job->financialAdjustments()->create([
            ...$request->validated(),
            'user_id' => $request->user()->id,
            'created_at' => now(),
        ]);

        return new FinancialAdjustmentResource($adjustment->load('user'));
    }
}
