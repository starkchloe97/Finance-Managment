<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\InvestmentRequest;
use App\Http\Resources\InvestmentResource;
use App\Models\Investment;
use App\Models\Investor;
use App\Services\InvestmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use App\Services\InvestmentLifecycleService;

class InvestmentController extends Controller
{
    public function __construct(
        private InvestmentService $investmentService
    ) {
    }

    public function index(): AnonymousResourceCollection
    {
        $investments = $this->investmentService->paginate();

        return InvestmentResource::collection($investments);
    }

    public function store(
        InvestmentRequest $request
    ): InvestmentResource {
        $investment = $this->investmentService->create(
            $request->validated()
        );

        return new InvestmentResource($investment);
    }

    public function show(
        Investment $investment
    ): InvestmentResource {
        $investment = $this->investmentService->find(
            $investment->id
        );

        return new InvestmentResource($investment);
    }

    public function update(
        InvestmentRequest $request,
        Investment $investment
    ): InvestmentResource {
        $investment = $this->investmentService->update(
            $investment,
            $request->validated()
        );

        return new InvestmentResource($investment);
    }

    public function destroy(
        Investment $investment
    ): JsonResponse {
        $this->investmentService->delete($investment);

        return response()->json([
            'message' => 'Investment deleted successfully.',
        ]);
    }
public function investorInvestments(
    Investor $investor
): AnonymousResourceCollection {
    $investments = Investment::query()
        ->where('investor_id', $investor->id)
        ->with('investor')
        ->latest()
        ->paginate(15);

    return InvestmentResource::collection(
        $investments
    );
}
public function mature(
    Investment $investment,
    InvestmentLifecycleService $lifecycle
) {
    $investment = $lifecycle->mature($investment);

    return new InvestmentResource($investment);
}

public function withdraw(
    Investment $investment,
    InvestmentLifecycleService $lifecycle
) {
    $investment = $lifecycle->withdraw($investment);

    return new InvestmentResource($investment);
}

public function settle(
    Investment $investment,
    InvestmentLifecycleService $lifecycle
) {
    $investment = $lifecycle->settle($investment);

    return new InvestmentResource($investment);
}

public function cancel(
    Investment $investment,
    InvestmentLifecycleService $lifecycle
) {
    $investment = $lifecycle->cancel($investment);

    return new InvestmentResource($investment);
}
}