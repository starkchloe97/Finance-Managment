<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProfitDistributionRequest;
use App\Http\Resources\InvestorProfitDistributionResource;
use App\Models\Investment;
use App\Models\Investor;
use App\Models\TransportJob;
use App\Services\InvestorPayoutService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class InvestorProfitDistributionController extends Controller
{
    public function byJob(TransportJob $job): AnonymousResourceCollection
    {
        return InvestorProfitDistributionResource::collection($job->profitDistributions()->with('investor')->latest()->paginate(15));
    }

    public function store(ProfitDistributionRequest $request, TransportJob $job, InvestorPayoutService $service): InvestorProfitDistributionResource
    {
        return new InvestorProfitDistributionResource($service->calculateDistribution($job, Investment::findOrFail($request->integer('investment_id')), $request->input('notes')));
    }

    public function byInvestment(Investment $investment): AnonymousResourceCollection
    {
        return InvestorProfitDistributionResource::collection($investment->profitDistributions()->with('investor')->latest()->paginate(15));
    }

    public function byInvestor(Investor $investor): AnonymousResourceCollection
    {
        return InvestorProfitDistributionResource::collection($investor->profitDistributions()->with('investor')->latest()->paginate(15));
    }
}
