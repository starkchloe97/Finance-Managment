<?php

namespace App\Services;

use App\Helpers\NumberGenerator;
use App\Models\VehicleContract;

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
        $data['contract_number'] = NumberGenerator::generate(
            'CON',
            VehicleContract::class
        );

        $data['total_monthly_rental'] =
            (float) $data['total_vehicles']
            * (float) $data['monthly_rental_per_vehicle'];

        return VehicleContract::create($data);
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