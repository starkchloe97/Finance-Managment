<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\InvestorLoanIndexRequest;
use App\Http\Requests\LoanIndexRequest;
use App\Http\Requests\LoanRepaymentRequest;
use App\Http\Requests\LoanRequest;
use App\Http\Requests\LoanUpdateRequest;
use App\Http\Resources\LoanRepaymentResource;
use App\Http\Resources\LoanResource;
use App\Models\Investor;
use App\Models\Loan;
use App\Services\LoanService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LoanController extends Controller
{
    public function __construct(private LoanService $loans) {}

    public function index(LoanIndexRequest $request): AnonymousResourceCollection
    {
        return LoanResource::collection($this->loans->paginate($request->validated()));
    }

    public function store(LoanRequest $request): LoanResource
    {
        return new LoanResource($this->loans->create($request->validated(), $request->user()));
    }

    public function show(Loan $loan): LoanResource
    {
        return new LoanResource($this->loans->find($loan));
    }

    public function update(LoanUpdateRequest $request, Loan $loan): LoanResource
    {
        return new LoanResource($this->loans->update($loan, $request->validated()));
    }

    public function repayments(Loan $loan): AnonymousResourceCollection
    {
        return LoanRepaymentResource::collection($this->loans->find($loan)->repayments);
    }

    public function repay(LoanRepaymentRequest $request, Loan $loan): LoanRepaymentResource
    {
        return new LoanRepaymentResource($this->loans->repay($loan, $request->validated(), $request->user()));
    }

    public function cancel(Loan $loan): LoanResource
    {
        return new LoanResource($this->loans->cancel($loan, request()->user()));
    }

    public function investorLoans(InvestorLoanIndexRequest $request, Investor $investor): AnonymousResourceCollection
    {
        $result = $this->loans->investorLoans($investor, $request->integer('per_page', 15));

        return LoanResource::collection($result['loans'])->additional([
            'meta' => ['loan_totals' => $result['totals']],
        ]);
    }
}
