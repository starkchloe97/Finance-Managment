<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\InvestorRequest;
use App\Http\Resources\InvestorResource;
use App\Models\Investor;
use App\Services\InvestorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class InvestorController extends Controller
{
    public function __construct(
        private InvestorService $investorService
    ) {
    }

    public function index(): AnonymousResourceCollection
    {
        $investors = $this->investorService->paginate();

        return InvestorResource::collection($investors);
    }

    public function store(InvestorRequest $request): InvestorResource
    {
        $investor = $this->investorService->create(
            $request->validated()
        );

        return new InvestorResource($investor);
    }

    public function show(Investor $investor): InvestorResource
    {
        return new InvestorResource($investor);
    }

    public function update(
        InvestorRequest $request,
        Investor $investor
    ): InvestorResource {
        $investor = $this->investorService->update(
            $investor,
            $request->validated()
        );

        return new InvestorResource($investor);
    }

    public function destroy(Investor $investor): JsonResponse
    {
        $this->investorService->delete($investor);

        return response()->json([
            'message' => 'Investor deleted successfully.',
        ]);
    }
}