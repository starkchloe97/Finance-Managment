<?php

use App\Http\Controllers\Api\V1\VehicleContractController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource(
        'vehicle-contracts',
        VehicleContractController::class
    );
});