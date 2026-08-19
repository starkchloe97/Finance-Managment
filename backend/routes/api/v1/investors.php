<?php

use App\Http\Controllers\Api\V1\InvestorController;
use App\Http\Controllers\Api\V1\InvestmentController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource(
        'investors',
        InvestorController::class
    );
    
    Route::get(
    'investors/{investor}/investments',
    [InvestmentController::class, 'investorInvestments']
);
    Route::apiResource(
        'investments',
        InvestmentController::class
    );
    Route::post(
    '/investments/{investment}/mature',
    [InvestmentController::class, 'mature']
);

Route::post(
    '/investments/{investment}/withdraw',
    [InvestmentController::class, 'withdraw']
);

Route::post(
    '/investments/{investment}/settle',
    [InvestmentController::class, 'settle']
);

Route::post(
    '/investments/{investment}/cancel',
    [InvestmentController::class, 'cancel']
);
});