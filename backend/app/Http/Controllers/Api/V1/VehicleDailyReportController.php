<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\VehicleDailyReportRequest;
use App\Http\Resources\VehicleDailyReportResource;
use App\Models\ContractVehicle;
use App\Models\VehicleDailyReport;
use App\Services\VehicleDailyReportCalculationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

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
}