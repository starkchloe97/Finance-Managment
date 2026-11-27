<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\AccountingPeriodCloseRequest;
use App\Models\AccountingPeriodClose;
use App\Services\AccountingPeriodService;
use App\Services\FinancialReportService;
use Illuminate\Http\JsonResponse;

class AccountingPeriodController extends Controller
{
    public function __construct(private AccountingPeriodService $periods) {}

    public function index(): JsonResponse
    {
        return response()->json(['data' => $this->periods->history()]);
    }

    public function store(
        AccountingPeriodCloseRequest $request,
        FinancialReportService $reports,
    ): JsonResponse {
        $close = $this->periods->close(
            $request->validated('period_type'),
            $request->validated('period_label'),
            $request->user()?->id,
            $reports,
        );

        return response()->json(['data' => $close], 201);
    }

    public function show(AccountingPeriodClose $period): JsonResponse
    {
        return response()->json(['data' => $period->load('closer:id,name')]);
    }
}
