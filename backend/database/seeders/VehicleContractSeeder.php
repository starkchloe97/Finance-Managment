<?php

namespace Database\Seeders;

use App\Models\ContractVehicle;
use App\Models\VehicleContract;
use App\Models\VehicleDailyReport;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class VehicleContractSeeder extends Seeder
{
    public function run(): void
    {
        $contracts = VehicleContract::factory(5)->create();

        foreach ($contracts as $contract) {
            $vehicleCount = $contract->total_vehicles;

            for ($i = 1; $i <= $vehicleCount; $i++) {
                $vehicle = ContractVehicle::factory()->create([
                    'vehicle_contract_id' => $contract->id,
                    'vehicle_number' => $contract->contract_number.'-'.str_pad((string) $i, 3, '0', STR_PAD_LEFT),
                    'monthly_rental' => $contract->monthly_rental_per_vehicle,
                    'duty_hours_per_day' => $contract->duty_hours_per_day,
                    'duty_days_per_week' => $contract->duty_days_per_week,
                    'public_holiday_rate' => $contract->public_holiday_rate,
                    'overtime_rate' => $contract->overtime_rate,
                    'monthly_mileage_limit' => $contract->monthly_mileage_limit,
                    'excess_mileage_rate' => $contract->excess_mileage_rate,
                    'status' => $contract->status === 'completed' ? 'completed' : 'active',
                ]);

                $this->createDailyReports($vehicle, $contract);
            }
        }
    }

    private function createDailyReports(ContractVehicle $vehicle, VehicleContract $contract): void
    {
        $startDate = Carbon::parse(max($contract->agreement_date, now()->subMonths(3)));
        $endDate = Carbon::parse(min($contract->end_date, now()->subDay()));

        if ($startDate->isAfter($endDate)) {
            return;
        }

        $reportCount = min(30, $startDate->diffInDays($endDate) + 1);
        $dates = [];
        $current = $startDate->copy();

        while ($current->lessThanOrEqualTo($endDate) && count($dates) < $reportCount) {
            if (fake()->boolean(80)) {
                $dates[] = $current->copy();
            }
            $current->addDay();
        }

        foreach ($dates as $date) {
            VehicleDailyReport::factory()->create([
                'contract_vehicle_id' => $vehicle->id,
                'report_date' => $date->toDateString(),
            ]);
        }
    }
}
