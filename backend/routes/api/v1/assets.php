<?php

use App\Http\Controllers\Api\V1\AssetController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Available company vehicles
    |--------------------------------------------------------------------------
    |
    | This route must be declared BEFORE apiResource().
    |
    */

    Route::get(
        'assets/available-vehicles',
        [AssetController::class, 'availableVehicles']
    );

    Route::apiResource(
        'assets',
        AssetController::class
    );
});