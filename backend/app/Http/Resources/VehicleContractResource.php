<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VehicleContractResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        
        return [
            
            'id' => $this->id,
            'contract_number' => $this->contract_number,

            'agreement_date' => $this->agreement_date?->format('Y-m-d'),

            'vendor_name' => $this->vendor_name,
            'vendor_address' => $this->vendor_address,

            'customer_name' => $this->customer_name,
            'customer_address' => $this->customer_address,
            'customer_tin' => $this->customer_tin,

            'end_date' => $this->end_date?->format('Y-m-d'),
            'duration_months' => $this->duration_months,

            'service_type' => $this->service_type,
            'fuel_included' => $this->fuel_included,
            'routine_maintenance_included' =>
            $this->routine_maintenance_included,

            'vehicles' => ContractVehicleResource::collection(
            $this->whenLoaded('vehicles')
            ),

            'total_vehicles' => $this->total_vehicles,
            'vehicle_make' => $this->vehicle_make,
            'vehicle_model' => $this->vehicle_model,
            'vehicle_model_year' => $this->vehicle_model_year,
            'vehicle_type' => $this->vehicle_type,

            'monthly_rental_per_vehicle' =>
                $this->monthly_rental_per_vehicle,
            'total_monthly_rental' =>
                $this->total_monthly_rental,

            'duty_hours_per_day' => $this->duty_hours_per_day,
            'duty_days_per_week' => $this->duty_days_per_week,
            'public_holiday_rate' =>
                $this->public_holiday_rate,
            'overtime_rate' =>
                $this->overtime_rate,

            'payment_terms' => $this->payment_terms,
            'advance_months' => $this->advance_months,

            'insurance_claim_period_days' =>
                $this->insurance_claim_period_days,

            'monthly_mileage_limit' =>
                $this->monthly_mileage_limit,
            'excess_mileage_rate' =>
                $this->excess_mileage_rate,

            'refrigeration_customer_responsibility' =>
                $this->refrigeration_customer_responsibility,

            'early_termination_months' =>
                $this->early_termination_months,

            'vendor_signatory_name' => $this->vendor_signatory_name,
            'vendor_signatory_designation' =>
                $this->vendor_signatory_designation,
            'vendor_signatory_cnic' => $this->vendor_signatory_cnic,
            'vendor_signature_date' =>
                $this->vendor_signature_date?->format('Y-m-d'),

            'customer_signatory_name' => $this->customer_signatory_name,
            'customer_signatory_designation' =>
                $this->customer_signatory_designation,
            'customer_signatory_cnic' => $this->customer_signatory_cnic,
            'customer_signature_date' =>
                $this->customer_signature_date?->format('Y-m-d'),

            'witness_1_name' => $this->witness_1_name,
            'witness_1_cnic' => $this->witness_1_cnic,

            'witness_2_name' => $this->witness_2_name,
            'witness_2_cnic' => $this->witness_2_cnic,

            'status' => $this->status,
            'notes' => $this->notes,

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}