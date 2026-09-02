<?php

namespace App\Services;

use App\Models\ContractVehicle;
use Illuminate\Support\Carbon;

class VehicleDailyReportCalculationService
{
    public function calculate(
        ContractVehicle $contractVehicle,
        array $data
    ): array {
        $totalMinutes = $this->calculateTotalMinutes(
            $data['time_in'] ?? null,
            $data['time_out'] ?? null
        );

        $normalMinutes = (int) round(
            ((float) ($contractVehicle->duty_hours_per_day ?? 10)) * 60
        );

        $overtimeMinutes = max(
            0,
            $totalMinutes - $normalMinutes
        );

        $overtimeRate = (float) (
            $contractVehicle->overtime_rate ?? 0
        );

        $isSpecialDay =
            !empty($data['is_public_holiday']) ||
            !empty($data['is_weekly_off']);

        /*
         * Normal day:
         *
         * 10 hours included
         * Extra time charged at hourly OT rate.
         */
        if (!$isSpecialDay) {
            $overtimeAmount = round(
                ($overtimeMinutes / 60) * $overtimeRate,
                2
            );
        } else {
            /*
             * Public holiday / weekly off:
             *
             * Up to normal duty hours = special-day rate.
             *
             * Extra hours = normal OT rate.
             */
            $holidayRate = (float) (
                $contractVehicle->public_holiday_rate ?? 0
            );

            if ($totalMinutes > 0) {
                $overtimeAmount = $holidayRate;

                if ($overtimeMinutes > 0) {
                    $overtimeAmount += round(
                        ($overtimeMinutes / 60) * $overtimeRate,
                        2
                    );
                }
            } else {
                $overtimeAmount = 0;
            }
        }

        $totalRunning = $this->calculateRunning(
            $data['meter_in'] ?? null,
            $data['meter_out'] ?? null
        );

        return [
            'total_minutes' => $totalMinutes,

            'normal_minutes' => min(
                $totalMinutes,
                $normalMinutes
            ),

            'overtime_minutes' => $overtimeMinutes,

            'overtime_amount' => $overtimeAmount,

            'total_running' => $totalRunning,
        ];
    }

    private function calculateTotalMinutes(
        ?string $timeIn,
        ?string $timeOut
    ): int {
        if (!$timeIn || !$timeOut) {
            return 0;
        }

        $start = Carbon::createFromFormat(
            'H:i',
            $timeIn
        );

        $end = Carbon::createFromFormat(
            'H:i',
            $timeOut
        );

        if ($end->lessThanOrEqualTo($start)) {
            $end->addDay();
        }

        return $start->diffInMinutes($end);
    }

    private function calculateRunning(
        $meterIn,
        $meterOut
    ): float {
        if (
            $meterIn === null ||
            $meterOut === null
        ) {
            return 0;
        }

        return max(
            0,
            (float) $meterOut - (float) $meterIn
        );
    }
}