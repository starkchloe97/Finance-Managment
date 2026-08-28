<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CompanyCapitalAddRequest;
use App\Http\Requests\CompanyCapitalAvailabilityRequest;
use App\Http\Requests\CompanyCapitalDraftConvertRequest;
use App\Http\Requests\CompanyCapitalDraftRemoveRequest;
use App\Http\Requests\CompanyCapitalOpeningBalanceRequest;
use App\Http\Requests\CompanyCapitalWithdrawRequest;
use App\Http\Resources\CompanyCapitalResource;
use App\Models\CompanyCapitalTransaction;
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

    public function store(CompanyCapitalAddRequest $request): CompanyCapitalResource
    {
        $status = $request->validated('status');

        if ($status === 'draft') {
            $this->capital->createDraft(
                (float) $request->validated('amount'),
                $request->validated('transaction_date'),
                $request->validated('notes') ?? '',
                $request->user(),
            );
        } else {
            $this->capital->addCapital(
                (float) $request->validated('amount'),
                $request->validated('transaction_date'),
                $status === 'available',
                $request->validated('notes'),
                $request->user(),
            );
        }

        return new CompanyCapitalResource($this->capital->snapshot());
    }

    public function withdraw(CompanyCapitalWithdrawRequest $request): CompanyCapitalResource
    {
        return new CompanyCapitalResource($this->capital->withdrawCapital(
            (float) $request->validated('amount'),
            $request->validated('transaction_date'),
            $request->validated('notes'),
            $request->user(),
        ));
    }

    public function updateAvailability(CompanyCapitalAvailabilityRequest $request, CompanyCapitalTransaction $transaction): CompanyCapitalResource
    {
        if ($request->boolean('available')) {
            $this->capital->makeAvailable($transaction->id);
        } else {
            $this->capital->makeUnavailable($transaction->id);
        }

        return new CompanyCapitalResource($this->capital->snapshot());
    }


    public function convertDraft(CompanyCapitalDraftConvertRequest $request, int $draft): CompanyCapitalResource
    {
        return new CompanyCapitalResource($this->capital->convertDraft(
            $draft,
            $request->boolean('available'),
            $request->user(),
        ));
    }

    public function removeDraft(CompanyCapitalDraftRemoveRequest $request, int $draft): CompanyCapitalResource
    {
        return new CompanyCapitalResource($this->capital->removeDraft(
            $draft,
            $request->validated('removal_note'),
            $request->user(),
        ));
    }
}
