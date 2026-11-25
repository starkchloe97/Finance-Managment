<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\RecordInvestmentMonthlyReturnRequest;
use App\Http\Resources\InvestmentMonthlyReturnResource;
use App\Models\Investment;
use App\Models\InvestmentMonthlyReturn;
use App\Services\InvestmentMonthlyReturnService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class InvestmentMonthlyReturnController extends Controller
{
    public function index(Investment $investment): AnonymousResourceCollection
    {
        return InvestmentMonthlyReturnResource::collection(
            $investment->monthlyReturns()->orderBy('installment_number')->get()
        );
    }

    public function pay(
        RecordInvestmentMonthlyReturnRequest $request,
        Investment $investment,
        InvestmentMonthlyReturn $monthlyReturn,
        InvestmentMonthlyReturnService $monthlyReturns
    ): InvestmentMonthlyReturnResource {
        return new InvestmentMonthlyReturnResource(
            $monthlyReturns->recordPayment(
                $investment,
                $monthlyReturn,
                $request->validated()
            )
        );
    }
}
