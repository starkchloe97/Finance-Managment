<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\EstimateController;
use App\Http\Controllers\Api\V1\TransportJobController;
use App\Http\Controllers\Api\V1\TransportJobBudgetController;

Route::middleware('auth:sanctum')->group(function () {
    
    Route::post(
        '/estimates/{estimate}/convert',
        [TransportJobController::class, 'convert']
    );
    Route::put(
        '/jobs/{job}/budget',
        [TransportJobBudgetController::class, 'update']
    );
});