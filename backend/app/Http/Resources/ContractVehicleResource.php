<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContractVehicleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'vehicle_contract_id' => $this->vehicle_contract_id,

            'vehicle_number' => $this->vehicle_number,

            'make' => $this->make,

            'model' => $this->model,

            'model_year' => $this->model_year,

            'vehicle_type' => $this->vehicle_type,

            'monthly_rental' => $this->monthly_rental,

            'duty_hours_per_day' => $this->duty_hours_per_day,

            'duty_days_per_week' => $this->duty_days_per_week,

            'public_holiday_rate' => $this->public_holiday_rate,

            'overtime_rate' => $this->overtime_rate,

            'monthly_mileage_limit' => $this->monthly_mileage_limit,

            'excess_mileage_rate' => $this->excess_mileage_rate,

            'status' => $this->status,

            'notes' => $this->notes,
        ];
    }
}
