<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\CustomerController;

Route::middleware('auth:sanctum')->group(function () {

    Route::apiResource('customers', CustomerController::class);

});