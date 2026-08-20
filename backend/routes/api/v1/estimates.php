<?php

use App\Http\Controllers\Api\V1\EstimateController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {

    // Estimates are customer-facing: they hold the quote, not the job's
    // internal cost/profit tracking. Editing (and showing) is available for
    // quotes that have not been converted yet — conversion is what closes them.
    Route::apiResource('estimates', EstimateController::class)
        ->only(['index', 'store', 'show', 'update']);

});
