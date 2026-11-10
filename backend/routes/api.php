<?php

use App\Http\Controllers\Api\V1\AssetController;
use App\Http\Controllers\Api\V1\ActivityLogController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BankAccountController;
use App\Http\Controllers\Api\V1\CompanyCapitalController;
use App\Http\Controllers\Api\V1\ContractVehicleController;
use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\EstimateController;
use App\Http\Controllers\Api\V1\FinancialAdjustmentController;
use App\Http\Controllers\Api\V1\InvestmentAllocationController;
use App\Http\Controllers\Api\V1\InvestmentController;
use App\Http\Controllers\Api\V1\InvestorController;
use App\Http\Controllers\Api\V1\InvestorProfitDistributionController;
use App\Http\Controllers\Api\V1\InvoiceController;
use App\Http\Controllers\Api\V1\LoanBorrowerController;
use App\Http\Controllers\Api\V1\LoanController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\TransportJobActivityController;
use App\Http\Controllers\Api\V1\TransportJobController;
use App\Http\Controllers\Api\V1\TransportJobExpenseController;
use App\Http\Controllers\Api\V1\VehicleContractController;
use App\Http\Controllers\Api\V1\VehicleDailyReportController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('/login', [AuthController::class, 'login']);

        Route::middleware('auth:sanctum')->group(function () {
            Route::get('/me', [AuthController::class, 'me']);
            Route::post('/logout', [AuthController::class, 'logout']);
        });
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('activities', [ActivityLogController::class, 'index']);
        Route::get('bank-accounts', [BankAccountController::class, 'index']);
        Route::post('bank-accounts', [BankAccountController::class, 'store']);
        Route::get('bank-accounts/{bankAccount}', [BankAccountController::class, 'show']);
        Route::get('bank-accounts/{bankAccount}/transactions', [BankAccountController::class, 'transactions']);

        Route::apiResource('customers', CustomerController::class);

        // Estimates are customer-facing: they hold the quote, not the job's
        // internal cost/profit tracking. Editing (and showing) is available
        // for quotes that have not been converted yet.
        Route::apiResource('estimates', EstimateController::class)
            ->only(['index', 'store', 'show', 'update']);

        Route::get('/jobs', [TransportJobController::class, 'index']);
        Route::get('/jobs/{job}', [TransportJobController::class, 'show']);
        Route::patch('/jobs/{job}/status', [TransportJobController::class, 'updateStatus']);
        Route::patch('/jobs/{job}/notes', [TransportJobController::class, 'updateNotes']);
        Route::get('/jobs/{job}/activities', [TransportJobActivityController::class, 'index']);
        Route::post('/estimates/{estimate}/convert', [TransportJobController::class, 'convert']);

        Route::scopeBindings()->group(function () {
            Route::post('/jobs/{job}/expenses', [TransportJobExpenseController::class, 'store']);
            Route::patch('/jobs/{job}/expenses/{expense}', [TransportJobExpenseController::class, 'update']);
            Route::delete('/jobs/{job}/expenses/{expense}', [TransportJobExpenseController::class, 'destroy']);
        });

        Route::get('/dashboard', [ReportController::class, 'dashboard']);
        Route::get('/reports/summary', [ReportController::class, 'summary']);

        Route::apiResource('investors', InvestorController::class);
        Route::get('investors/{investor}/investments', [InvestmentController::class, 'investorInvestments']);
        Route::apiResource('investments', InvestmentController::class);
        Route::post('/investments/{investment}/mature', [InvestmentController::class, 'mature']);
        Route::post('/investments/{investment}/withdraw', [InvestmentController::class, 'withdraw']);
        Route::post('/investments/{investment}/settle', [InvestmentController::class, 'settle']);
        Route::post('/investments/{investment}/cancel', [InvestmentController::class, 'cancel']);

        Route::get('investments/{investment}/allocations', [InvestmentAllocationController::class, 'index']);
        Route::post('investments/{investment}/allocations', [InvestmentAllocationController::class, 'store']);
        Route::delete('investment-allocations/{allocation}', [InvestmentAllocationController::class, 'destroy']);
        Route::get('jobs/{job}/profit-distributions', [InvestorProfitDistributionController::class, 'byJob']);
        Route::post('jobs/{job}/profit-distributions', [InvestorProfitDistributionController::class, 'store']);
        Route::get('investments/{investment}/profit-distributions', [InvestorProfitDistributionController::class, 'byInvestment']);
        Route::get('investors/{investor}/profit-distributions', [InvestorProfitDistributionController::class, 'byInvestor']);
        Route::get('jobs/{job}/financial-adjustments', [FinancialAdjustmentController::class, 'index']);
        Route::post('jobs/{job}/financial-adjustments', [FinancialAdjustmentController::class, 'store']);

        Route::get('company-capital', [CompanyCapitalController::class, 'show']);
        Route::post('company-capital/initialize', [CompanyCapitalController::class, 'initialize']);
        Route::post('company-capital', [CompanyCapitalController::class, 'store']);
        Route::post('company-capital/withdraw', [CompanyCapitalController::class, 'withdraw']);
        Route::patch('company-capital/transactions/{transaction}', [CompanyCapitalController::class, 'updateAvailability']);
        Route::get('company-capital/profit', [CompanyCapitalController::class, 'profitSnapshot']);
        Route::post('company-capital/profit/add', [CompanyCapitalController::class, 'addProfitToCapital']);
        Route::post('company-capital/drafts/{draft}/convert', [CompanyCapitalController::class, 'convertDraft']);
        Route::post('company-capital/drafts/{draft}/remove', [CompanyCapitalController::class, 'removeDraft']);

        Route::get('loan-borrowers', [LoanBorrowerController::class, 'index']);
        Route::post('loan-borrowers', [LoanBorrowerController::class, 'store']);
        Route::get('investors/{investor}/loans', [LoanController::class, 'investorLoans']);
        Route::get('loans', [LoanController::class, 'index']);
        Route::post('loans', [LoanController::class, 'store']);
        Route::get('loans/{loan}', [LoanController::class, 'show']);
        Route::put('loans/{loan}', [LoanController::class, 'update']);
        Route::post('loans/{loan}/repayments', [LoanController::class, 'repay']);
        Route::get('loans/{loan}/repayments', [LoanController::class, 'repayments']);
        Route::post('loans/{loan}/cancel', [LoanController::class, 'cancel']);

        Route::get('assets/available-vehicles', [AssetController::class, 'availableVehicles']);
        Route::apiResource('assets', AssetController::class);

        Route::apiResource('vehicle-contracts', VehicleContractController::class);
        Route::get('contract-vehicles', [ContractVehicleController::class, 'index']);
        Route::prefix('contract-vehicles/{contractVehicle}')->group(function () {
            Route::get('/', [ContractVehicleController::class, 'show']);
            Route::get('daily-reports/monthly-summary', [VehicleDailyReportController::class, 'monthlySummary']);
            Route::apiResource('daily-reports', VehicleDailyReportController::class)
                ->parameters(['daily-reports' => 'dailyReport']);
        });

        Route::get('/invoices', [InvoiceController::class, 'index']);
        Route::post('/invoices', [InvoiceController::class, 'store']);
        Route::get('/invoices/company-details', [InvoiceController::class, 'companyDetails']);
        Route::get('/invoices/{invoice}', [InvoiceController::class, 'show']);
        Route::put('/invoices/{invoice}', [InvoiceController::class, 'update']);
        Route::get('/receivables', [InvoiceController::class, 'index'])->name('receivables');
        Route::get('/payables', [InvoiceController::class, 'index'])->name('payables');
        Route::get('/commission/receivables', [InvoiceController::class, 'commission']);
        Route::get('/payments', [InvoiceController::class, 'paymentList']);
        Route::post('/payments', [InvoiceController::class, 'storePayment']);
        Route::post('/jobs/{job}/invoice', [InvoiceController::class, 'forJob']);
    });
});
