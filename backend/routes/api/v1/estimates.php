<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\EstimateController;

Route::middleware('auth:sanctum')->group(function () {

    // Editing an estimate is not built yet, so only these two exist.
    Route::apiResource('estimates', EstimateController::class)
        ->only(['index', 'store']);

});
