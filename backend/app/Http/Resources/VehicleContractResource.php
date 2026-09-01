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

            'vendor' => [
                'name' => $this->vendor_name,
                'address' => $this->vendor_address,
            ],

            'customer' => [
                'name' => $this->customer_name,
                'address' => $this->customer_address,
                'tin' => $this->customer_tin,
            ],

            'effective_date' => $this->effective_date?->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'duration_months' => $this->duration_months,

            'service_type' => $this->service_type,
            'fuel_included' => $this->fuel_included,
            'routine_maintenance_included' =>
                $this->routine_maintenance_included,

            'vehicle' => [
                'total_vehicles' => $this->total_vehicles,
                'make' => $this->vehicle_make,
                'model' => $this->vehicle_model,
                'model_year' => $this->vehicle_model_year,
                'type' => $this->vehicle_type,
            ],

            'rental' => [
                'monthly_per_vehicle' =>
                    $this->monthly_rental_per_vehicle,
                'total_monthly' =>
                    $this->total_monthly_rental,
            ],

            'duty' => [
                'hours_per_day' => $this->duty_hours_per_day,
                'days_per_week' => $this->duty_days_per_week,
                'public_holiday_rate' =>
                    $this->public_holiday_rate,
                'overtime_rate' =>
                    $this->overtime_rate,
            ],

            'payment' => [
                'terms' => $this->payment_terms,
                'advance_months' => $this->advance_months,
            ],

            'insurance_claim_period_days' =>
                $this->insurance_claim_period_days,

            'mileage' => [
                'monthly_limit' =>
                    $this->monthly_mileage_limit,
                'excess_rate' =>
                    $this->excess_mileage_rate,
            ],

            'refrigeration_customer_responsibility' =>
                $this->refrigeration_customer_responsibility,

            'early_termination_months' =>
                $this->early_termination_months,

            'signatures' => [
                'vendor' => [
                    'name' => $this->vendor_signatory_name,
                    'designation' =>
                        $this->vendor_signatory_designation,
                    'cnic' => $this->vendor_signatory_cnic,
                    'date' =>
                        $this->vendor_signature_date?->format('Y-m-d'),
                ],

                'customer' => [
                    'name' => $this->customer_signatory_name,
                    'designation' =>
                        $this->customer_signatory_designation,
                    'cnic' => $this->customer_signatory_cnic,
                    'date' =>
                        $this->customer_signature_date?->format('Y-m-d'),
                ],

                'witness_1' => [
                    'name' => $this->witness_1_name,
                    'cnic' => $this->witness_1_cnic,
                ],

                'witness_2' => [
                    'name' => $this->witness_2_name,
                    'cnic' => $this->witness_2_cnic,
                ],
            ],

            'status' => $this->status,
            'notes' => $this->notes,

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}