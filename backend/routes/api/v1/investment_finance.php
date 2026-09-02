<?php

use App\Http\Controllers\Api\V1\FinancialAdjustmentController;
use App\Http\Controllers\Api\V1\InvestmentAllocationController;
use App\Http\Controllers\Api\V1\InvestorProfitDistributionController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('investments/{investment}/allocations', [InvestmentAllocationController::class, 'index']);
    Route::post('investments/{investment}/allocations', [InvestmentAllocationController::class, 'store']);
    Route::delete('investment-allocations/{allocation}', [InvestmentAllocationController::class, 'destroy']);
    Route::get('jobs/{job}/profit-distributions', [InvestorProfitDistributionController::class, 'byJob']);
    Route::post('jobs/{job}/profit-distributions', [InvestorProfitDistributionController::class, 'store']);
    Route::get('investments/{investment}/profit-distributions', [InvestorProfitDistributionController::class, 'byInvestment']);
    Route::get('investors/{investor}/profit-distributions', [InvestorProfitDistributionController::class, 'byInvestor']);
    Route::get('jobs/{job}/financial-adjustments', [FinancialAdjustmentController::class, 'index']);
    Route::post('jobs/{job}/financial-adjustments', [FinancialAdjustmentController::class, 'store']);
});
