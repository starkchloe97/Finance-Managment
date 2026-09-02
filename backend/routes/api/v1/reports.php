<?php

use App\Http\Controllers\Api\V1\ReportController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/dashboard', [ReportController::class, 'dashboard']);
    Route::get('/reports/summary', [ReportController::class, 'summary']);

});
