<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\EstimateController;

Route::middleware('auth:sanctum')->group(function () {

    Route::apiResource(
        'estimates',
        EstimateController::class
    );

});