<?php

use App\Http\Controllers\Api\V1\CompanyCapitalController;
use App\Http\Controllers\Api\V1\LoanBorrowerController;
use App\Http\Controllers\Api\V1\LoanController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('company-capital', [CompanyCapitalController::class, 'show']);
    Route::post('company-capital/initialize', [CompanyCapitalController::class, 'initialize']);

    Route::get('loan-borrowers', [LoanBorrowerController::class, 'index']);
    Route::post('loan-borrowers', [LoanBorrowerController::class, 'store']);

    Route::get('investors/{investor}/loans', [LoanController::class, 'investorLoans']);
    Route::get('loans', [LoanController::class, 'index']);
    Route::post('loans', [LoanController::class, 'store']);
    Route::get('loans/{loan}', [LoanController::class, 'show']);
    Route::put('loans/{loan}', [LoanController::class, 'update']);
    Route::post('loans/{loan}/repayments', [LoanController::class, 'repay']);
    Route::get('loans/{loan}/repayments', [LoanController::class, 'repayments']);
    Route::post('loans/{loan}/cancel', [LoanController::class, 'cancel']);
});
