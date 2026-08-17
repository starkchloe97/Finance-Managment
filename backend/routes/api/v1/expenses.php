<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\TransportJobExpenseController;

Route::middleware('auth:sanctum')->group(function () {

    Route::post(
        '/jobs/{job}/expenses',
        [TransportJobExpenseController::class, 'store']
    );

    Route::put(
        '/expenses/{expense}',
        [TransportJobExpenseController::class, 'update']
    );

    Route::delete(
        '/expenses/{expense}',
        [TransportJobExpenseController::class, 'destroy']
    );

});
