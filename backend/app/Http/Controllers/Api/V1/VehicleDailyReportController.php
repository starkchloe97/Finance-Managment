<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\VehicleDailyReportRequest;
use App\Http\Resources\VehicleDailyReportResource;
use App\Models\ContractVehicle;
use App\Models\VehicleDailyReport;
use App\Services\VehicleDailyReportCalculationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Carbon;

class VehicleDailyReportController extends Controller
{
    public function __construct(
        private VehicleDailyReportCalculationService $calculator
    ) {}

    public function index(
        ContractVehicle $contractVehicle
    ): AnonymousResourceCollection {
        $reports = $contractVehicle
            ->dailyReports()
            ->latest('report_date')
            ->paginate(31);

        return VehicleDailyReportResource::collection(
            $reports
        );
    }

    public function store(
        VehicleDailyReportRequest $request,
        ContractVehicle $contractVehicle
    ): VehicleDailyReportResource {
        $data = $request->validated();

        $calculated = $this->calculator->calculate(
            $contractVehicle,
            $data
        );

        $report = $contractVehicle
            ->dailyReports()
            ->create([
                ...$data,
                ...$calculated,
            ]);

        return new VehicleDailyReportResource(
            $report->load('contractVehicle')
        );
    }

    public function show(
        ContractVehicle $contractVehicle,
        VehicleDailyReport $dailyReport
    ): VehicleDailyReportResource {
        abort_unless(
            $dailyReport->contract_vehicle_id
                === $contractVehicle->id,
            404
        );

        return new VehicleDailyReportResource(
            $dailyReport->load('contractVehicle')
        );
    }

    public function update(
        VehicleDailyReportRequest $request,
        ContractVehicle $contractVehicle,
        VehicleDailyReport $dailyReport
    ): VehicleDailyReportResource {
        abort_unless(
            $dailyReport->contract_vehicle_id
                === $contractVehicle->id,
            404
        );

        $data = $request->validated();

        $calculated = $this->calculator->calculate(
            $contractVehicle,
            $data
        );

        $dailyReport->update([
            ...$data,
            ...$calculated,
        ]);

        return new VehicleDailyReportResource(
            $dailyReport->fresh(
                'contractVehicle'
            )
        );
    }

    public function destroy(
        ContractVehicle $contractVehicle,
        VehicleDailyReport $dailyReport
    ): JsonResponse {
        abort_unless(
            $dailyReport->contract_vehicle_id
                === $contractVehicle->id,
            404
        );

        $dailyReport->delete();

        return response()->json([
            'message' =>
                'Daily report deleted successfully.',
        ]);
    }

    public function monthlySummary(
    Request $request,
    ContractVehicle $contractVehicle
): JsonResponse {
    $validated = $request->validate([
        'month' => [
            'required',
            'date_format:Y-m',
        ],
    ]);

    $start = Carbon::createFromFormat(
        'Y-m',
        $validated['month']
    )->startOfMonth();

    $end = $start->copy()->endOfMonth();

    $reports = $contractVehicle
        ->dailyReports()
        ->whereBetween('report_date', [
            $start->toDateString(),
            $end->toDateString(),
        ])
        ->get();

    $totalRunning = (float) $reports->sum(
        'total_running'
    );

    $monthlyLimit = (float) (
        $contractVehicle->monthly_mileage_limit ?? 0
    );

    $excessMileage = max(
        0,
        $totalRunning - $monthlyLimit
    );

    $excessRate = (float) (
        $contractVehicle->excess_mileage_rate ?? 0
    );

    $excessAmount = round(
        $excessMileage * $excessRate,
        2
    );

    return response()->json([
        'data' => [
            'contract_vehicle' => $contractVehicle,

            'month' => $validated['month'],

            'report_count' => $reports->count(),

            'total_running' => $totalRunning,

            'monthly_mileage_limit' => $monthlyLimit,

            'excess_mileage' => $excessMileage,

            'excess_mileage_rate' => $excessRate,

            'excess_mileage_amount' => $excessAmount,

            'total_overtime_minutes' =>
                $reports->sum('overtime_minutes'),

            'total_overtime_amount' =>
                $reports->sum('overtime_amount'),
        ],
    ]);
}
}