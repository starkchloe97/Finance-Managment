<?php

namespace Database\Factories;

use App\Models\VehicleDailyReport;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VehicleDailyReport>
 */
class VehicleDailyReportFactory extends Factory
{
    protected $model = VehicleDailyReport::class;

    public function definition(): array
    {
        $meterIn = fake()->numberBetween(10000, 50000);
        $kmDriven = fake()->numberBetween(50, 350);
        $meterOut = $meterIn + $kmDriven;

        $startHour = fake()->numberBetween(6, 10);
        $startMinute = fake()->numberBetween(0, 59);
        $durationHours = fake()->randomFloat(2, 8, 14);
        $endHour = min(23, (int) ($startHour + $durationHours));
        $endMinute = fake()->numberBetween(0, 59);

        $timeIn = sprintf('%02d:%02d:00', $startHour, $startMinute);
        $timeOut = sprintf('%02d:%02d:00', $endHour, $endMinute);

        $totalMinutes = (int) ((strtotime($timeOut) - strtotime($timeIn)) / 60);
        if ($totalMinutes < 0) {
            $totalMinutes += 24 * 60;
        }

        $dutyMinutes = 10 * 60;
        $overtimeMinutes = max(0, $totalMinutes - $dutyMinutes);
        $normalMinutes = $totalMinutes - $overtimeMinutes;

        $overtimeRate = fake()->randomFloat(2, 800, 1500);
        $overtimeAmount = round(($overtimeMinutes / 60) * $overtimeRate, 2);

        $mileageLimit = fake()->numberBetween(2000, 4000) / 30;
        $excessMileage = max(0, $kmDriven - $mileageLimit);
        $excessRate = fake()->randomFloat(2, 20, 60);
        $excessMileageAmount = round($excessMileage * $excessRate, 2);

        return [
            'contract_vehicle_id' => null, // set by seeder
            'report_date' => fake()->dateTimeBetween('-3 months', 'yesterday')->format('Y-m-d'),
            'time_in' => $timeIn,
            'time_out' => $timeOut,
            'meter_in' => $meterIn,
            'meter_out' => $meterOut,
            'fuel_drawn' => fake()->optional(0.4)->randomFloat(2, 20, 80),
            'total_minutes' => $totalMinutes,
            'normal_minutes' => $normalMinutes,
            'overtime_minutes' => $overtimeMinutes,
            'total_running' => round($totalMinutes / 60, 2),
            'overtime_amount' => $overtimeAmount,
            'excess_mileage' => round($excessMileage, 2),
            'excess_mileage_amount' => $excessMileageAmount,
            'is_public_holiday' => fake()->boolean(5),
            'is_weekly_off' => fake()->boolean(15),
            'status' => fake()->randomElement(['draft', 'approved', 'approved', 'approved', 'rejected']),
            'remarks' => fake()->optional(0.3)->sentence(),
        ];
    }
}
