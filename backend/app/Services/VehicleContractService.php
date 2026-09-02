<?php

namespace App\Services;

use App\Helpers\NumberGenerator;
use App\Models\VehicleContract;
use Illuminate\Support\Facades\DB;

class VehicleContractService
{
    public function paginate(array $filters = [])
    {
        $query = VehicleContract::query();

        if (!empty($filters['search'])) {
            $search = $filters['search'];

            $query->where(function ($q) use ($search) {
                $q->where(
                    'contract_number',
                    'like',
                    "%{$search}%"
                )->orWhere(
                    'vendor_name',
                    'like',
                    "%{$search}%"
                )->orWhere(
                    'customer_name',
                    'like',
                    "%{$search}%"
                )->orWhere(
                    'vehicle_make',
                    'like',
                    "%{$search}%"
                )->orWhere(
                    'vehicle_model',
                    'like',
                    "%{$search}%"
                );
            });
        }

        if (!empty($filters['status'])) {
            $query->where(
                'status',
                $filters['status']
            );
        }

        return $query
            ->latest()
            ->paginate(
                min(
                    max((int) ($filters['per_page'] ?? 10), 1),
                    100
                )
            );
    }

    public function create(array $data): VehicleContract
{
    return DB::transaction(function () use ($data) {

        $data['contract_number'] = NumberGenerator::generate(
            'CON',
            VehicleContract::class
        );

        $data['total_monthly_rental'] =
            (float) $data['total_vehicles']
            * (float) $data['monthly_rental_per_vehicle'];

        $contract = VehicleContract::create($data);

        $this->createContractVehicles(
            $contract,
            $data
        );

        return $contract->load('vehicles');
    });
}

private function createContractVehicles(
    VehicleContract $contract,
    array $data
): void {
    $quantity = (int) $data['total_vehicles'];

    for ($i = 0; $i < $quantity; $i++) {
        $contract->vehicles()->create([
            'vehicle_number' => null,

            'make' =>
                $data['vehicle_make'],

            'model' =>
                $data['vehicle_model'],

            'model_year' =>
                $data['vehicle_model_year'],

            'vehicle_type' =>
                $data['vehicle_type'],

            'monthly_rental' =>
                $data['monthly_rental_per_vehicle'],

            'duty_hours_per_day' =>
                $data['duty_hours_per_day'],

            'duty_days_per_week' =>
                $data['duty_days_per_week'],

            'public_holiday_rate' =>
                $data['public_holiday_rate'],

            'overtime_rate' =>
                $data['overtime_rate'],

            'monthly_mileage_limit' =>
                $data['monthly_mileage_limit'],

            'excess_mileage_rate' =>
                $data['excess_mileage_rate'],

            'status' => 'active',
        ]);
    }
}

    public function update(
        VehicleContract $contract,
        array $data
    ): VehicleContract {
        $data['total_monthly_rental'] =
            (float) $data['total_vehicles']
            * (float) $data['monthly_rental_per_vehicle'];

        $contract->update($data);

        return $contract->refresh();
    }

    public function delete(VehicleContract $contract): void
    {
        $contract->delete();
    }
}