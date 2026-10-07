<?php

use App\Http\Controllers\Api\V1\InvoiceController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/invoices', [InvoiceController::class, 'index']);
    Route::post('/invoices', [InvoiceController::class, 'store']);
    Route::get('/invoices/{invoice}', [InvoiceController::class, 'show']);
    Route::get('/receivables', [InvoiceController::class, 'index'])->name('receivables');
    Route::get('/payables', [InvoiceController::class, 'index'])->name('payables');
    Route::get('/commission/receivables', [InvoiceController::class, 'commission']);
    Route::get('/payments', [InvoiceController::class, 'paymentList']);
    Route::post('/payments', [InvoiceController::class, 'storePayment']);
    Route::post('/jobs/{job}/invoice', [InvoiceController::class, 'forJob']);
});
