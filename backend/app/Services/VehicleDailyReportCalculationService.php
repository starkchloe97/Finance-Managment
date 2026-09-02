<?php

namespace App\Services;

use App\Models\ContractVehicle;
use Carbon\Carbon;

class VehicleDailyReportCalculationService
{
    public function calculate(
        ContractVehicle $vehicle,
        array $data
    ): array {
        $timeIn = $data['time_in'] ?? null;
        $timeOut = $data['time_out'] ?? null;

        $totalMinutes = 0;

        if ($timeIn && $timeOut) {
            $start = Carbon::createFromFormat(
                'H:i',
                $timeIn
            );

            $end = Carbon::createFromFormat(
                'H:i',
                $timeOut
            );

            /*
             * Handle overnight shifts.
             */
            if ($end->lessThanOrEqualTo($start)) {
                $end->addDay();
            }

            $totalMinutes = $start->diffInMinutes($end);
        }

        $normalMinutes = min(
            $totalMinutes,
            ((int) $vehicle->duty_hours_per_day) * 60
        );

        $overtimeMinutes = max(
            0,
            $totalMinutes - $normalMinutes
        );

        /*
         * Overtime amount
         *
         * Example:
         * 90 minutes × Rs 300/hour
         *
         * = Rs 450
         */
        $overtimeAmount =
            ($overtimeMinutes / 60)
            * (float) $vehicle->overtime_rate;

        /*
         * Meter calculation
         */
        $meterIn = isset($data['meter_in'])
            ? (float) $data['meter_in']
            : null;

        $meterOut = isset($data['meter_out'])
            ? (float) $data['meter_out']
            : null;

        $totalRunning = 0;

        if (
            $meterIn !== null &&
            $meterOut !== null
        ) {
            $totalRunning = $meterOut - $meterIn;

            /*
             * Never allow a negative distance.
             * A negative value means the meter
             * data needs verification.
             */
            if ($totalRunning < 0) {
                $totalRunning = 0;
            }
        }

        return [
            'total_minutes' =>
                $totalMinutes,

            'normal_minutes' =>
                $normalMinutes,

            'overtime_minutes' =>
                $overtimeMinutes,

            'total_running' =>
                $totalRunning,

            'overtime_amount' =>
                round($overtimeAmount, 2),
        ];
    }
}