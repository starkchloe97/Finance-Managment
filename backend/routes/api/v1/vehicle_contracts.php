<?php

use App\Http\Controllers\Api\V1\VehicleContractController;
use App\Http\Controllers\Api\V1\VehicleDailyReportController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource(
        'vehicle-contracts',
        VehicleContractController::class
    );

    Route::prefix('contract-vehicles/{contractVehicle}')
        ->group(function () {
            Route::apiResource(
                'daily-reports',
                VehicleDailyReportController::class
            )->parameters([
                'daily-reports' => 'dailyReport',
            ]);

            Route::get(
                'daily-reports/monthly-summary',
                [VehicleDailyReportController::class, 'monthlySummary']
            );
        });
});