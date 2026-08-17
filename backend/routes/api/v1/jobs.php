<?php

use App\Http\Controllers\Api\V1\TransportJobActivityController;
use App\Http\Controllers\Api\V1\TransportJobController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/jobs', [TransportJobController::class, 'index']);

    Route::get('/jobs/{job}', [TransportJobController::class, 'show']);

    // Forward one stage only — the service decides what counts as next.
    Route::patch('/jobs/{job}/status', [TransportJobController::class, 'updateStatus']);

    Route::patch('/jobs/{job}/notes', [TransportJobController::class, 'updateNotes']);

    // Append-only history. Read here, written by the services that change the job.
    Route::get('/jobs/{job}/activities', [TransportJobActivityController::class, 'index']);

    // The customer accepted the estimate, so start the job.
    Route::post(
        '/estimates/{estimate}/convert',
        [TransportJobController::class, 'convert']
    );

});
