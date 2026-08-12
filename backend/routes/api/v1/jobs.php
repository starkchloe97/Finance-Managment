<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\TransportJobController;
use App\Http\Controllers\Api\V1\TransportJobBudgetController;

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/jobs', [TransportJobController::class, 'index']);

    Route::get('/jobs/{job}', [TransportJobController::class, 'show']);

    // The customer accepted the estimate.
    Route::post(
        '/estimates/{estimate}/convert',
        [TransportJobController::class, 'convert']
    );

    // Expected cost. Replaces every budget line.
    Route::put(
        '/jobs/{job}/budget',
        [TransportJobBudgetController::class, 'update']
    );

});
