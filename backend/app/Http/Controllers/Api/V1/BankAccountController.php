<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\BankAccountRequest;
use App\Http\Resources\BankAccountResource;
use App\Http\Resources\BankTransactionResource;
use App\Models\BankAccount;
use App\Services\BankAccountService;

class BankAccountController extends Controller
{
    public function __construct(private BankAccountService $service) {}

    public function index()
    {
        return BankAccountResource::collection(BankAccount::query()->orderBy('name')->get());
    }

    public function show(BankAccount $bankAccount)
    {
        return new BankAccountResource($bankAccount);
    }

    public function store(BankAccountRequest $request)
    {
        $account = $this->service->create($request->validated());

        return (new BankAccountResource($account))->response()->setStatusCode(201);
    }

    public function transactions(BankAccount $bankAccount)
    {
        return BankTransactionResource::collection(
            $bankAccount->transactions()->with('payment')->latest('transaction_date')->latest('id')->paginate(50)
        );
    }
}
