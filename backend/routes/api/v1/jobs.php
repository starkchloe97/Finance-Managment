<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\TransportJobController;

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/jobs', [TransportJobController::class, 'index']);

    Route::get('/jobs/{job}', [TransportJobController::class, 'show']);

    // The customer accepted the estimate, so start the job.
    Route::post(
        '/estimates/{estimate}/convert',
        [TransportJobController::class, 'convert']
    );

});
