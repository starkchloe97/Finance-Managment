<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CompanyCapitalOpeningBalanceRequest;
use App\Http\Resources\CompanyCapitalResource;
use App\Services\CompanyCapitalService;

class CompanyCapitalController extends Controller
{
    public function __construct(private CompanyCapitalService $capital) {}

    public function show(): CompanyCapitalResource
    {
        return new CompanyCapitalResource($this->capital->snapshot());
    }

    public function initialize(CompanyCapitalOpeningBalanceRequest $request): CompanyCapitalResource
    {
        return new CompanyCapitalResource($this->capital->initialize(
            (float) $request->validated('amount'),
            $request->validated('transaction_date'),
            $request->user(),
        ));
    }
}
