<?php

use App\Http\Controllers\Api\V1\TransportJobExpenseController;
use Illuminate\Support\Facades\Route;

/**
 * Actual cost. Each expense is recorded as it happens.
 *
 * All three are nested under the job and scoped to it, so an expense id from
 * one job cannot be edited or deleted through another — a mismatch is a 404
 * rather than a cross-job write.
 */
Route::middleware('auth:sanctum')->scopeBindings()->group(function () {

    Route::post('/jobs/{job}/expenses', [TransportJobExpenseController::class, 'store']);

    Route::patch('/jobs/{job}/expenses/{expense}', [TransportJobExpenseController::class, 'update']);

    Route::delete('/jobs/{job}/expenses/{expense}', [TransportJobExpenseController::class, 'destroy']);

});
