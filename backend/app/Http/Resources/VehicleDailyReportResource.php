<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VehicleDailyReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'contract_vehicle_id' => $this->contract_vehicle_id,

            'report_date' => $this->report_date?->format('Y-m-d'),

            'time_in' => $this->time_in,

            'time_out' => $this->time_out,

            'meter_in' => $this->meter_in,

            'meter_out' => $this->meter_out,

            'fuel_drawn' => $this->fuel_drawn,

            'total_minutes' => $this->total_minutes,

            'normal_minutes' => $this->normal_minutes,

            'overtime_minutes' => $this->overtime_minutes,

            'total_running' => $this->total_running,

            'overtime_amount' => $this->overtime_amount,

            'excess_mileage' => $this->excess_mileage,

            'excess_mileage_amount' => $this->excess_mileage_amount,

            'is_public_holiday' => $this->is_public_holiday,

            'is_weekly_off' => $this->is_weekly_off,

            'status' => $this->status,

            'remarks' => $this->remarks,

            'vehicle' => new ContractVehicleResource(
                $this->whenLoaded('contractVehicle')
            ),
        ];
    }
}
