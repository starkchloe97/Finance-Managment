<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\TransportJobExpenseController;

Route::middleware('auth:sanctum')->group(function () {

    // Actual cost. Each expense is recorded as it happens.
    Route::post(
        '/jobs/{job}/expenses',
        [TransportJobExpenseController::class, 'store']
    );

    Route::delete(
        '/expenses/{expense}',
        [TransportJobExpenseController::class, 'destroy']
    );

});
