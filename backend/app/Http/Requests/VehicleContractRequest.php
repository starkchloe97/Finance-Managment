<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VehicleContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'agreement_date' => ['required', 'date'],

            'vendor_name' => ['required', 'string', 'max:255'],
            'vendor_address' => ['nullable', 'string'],

            'customer_name' => ['required', 'string', 'max:255'],
            'customer_address' => ['nullable', 'string'],
            'customer_tin' => ['nullable', 'string', 'max:100'],

            'end_date' => ['required', 'date'],
            'duration_months' => ['nullable', 'integer', 'min:1'],

            'service_type' => [
                'required',
                Rule::in([
                    'with_driver',
                    'without_driver',
                ]),
            ],

            'fuel_included' => ['boolean'],
            'routine_maintenance_included' => ['boolean'],

            'total_vehicles' => ['required', 'integer', 'min:1'],

            'vehicle_make' => ['nullable', 'string', 'max:255'],
            'vehicle_model' => ['nullable', 'string', 'max:255'],
            'vehicle_model_year' => ['nullable', 'string', 'max:50'],
            'vehicle_type' => ['nullable', 'string', 'max:255'],

            'monthly_rental_per_vehicle' => ['required', 'numeric', 'min:0'],
            'total_monthly_rental' => ['required', 'numeric', 'min:0'],

            'duty_hours_per_day' => ['required', 'integer', 'min:0'],
            'duty_days_per_week' => ['required', 'integer', 'min:0', 'max:7'],

            'public_holiday_rate' => ['required', 'numeric', 'min:0'],
            'overtime_rate' => ['required', 'numeric', 'min:0'],

            'payment_terms' => ['nullable', 'string', 'max:255'],
            'advance_months' => ['required', 'integer', 'min:0'],

            'insurance_claim_period_days' => [
                'required',
                'integer',
                'min:0',
            ],

            'monthly_mileage_limit' => [
                'required',
                'integer',
                'min:0',
            ],

            'excess_mileage_rate' => [
                'required',
                'numeric',
                'min:0',
            ],

            'refrigeration_customer_responsibility' => [
                'boolean',
            ],

            'early_termination_months' => [
                'required',
                'integer',
                'min:0',
            ],

            'vendor_signatory_name' => ['nullable', 'string', 'max:255'],
            'vendor_signatory_designation' => ['nullable', 'string', 'max:255'],
            'vendor_signatory_cnic' => ['nullable', 'string', 'max:50'],
            'vendor_signature_date' => ['nullable', 'date'],

            'customer_signatory_name' => ['nullable', 'string', 'max:255'],
            'customer_signatory_designation' => ['nullable', 'string', 'max:255'],
            'customer_signatory_cnic' => ['nullable', 'string', 'max:50'],
            'customer_signature_date' => ['nullable', 'date'],

            'witness_1_name' => ['nullable', 'string', 'max:255'],
            'witness_1_cnic' => ['nullable', 'string', 'max:50'],

            'witness_2_name' => ['nullable', 'string', 'max:255'],
            'witness_2_cnic' => ['nullable', 'string', 'max:50'],

            'status' => [
                'nullable',
                Rule::in([
                    'draft',
                    'active',
                    'expired',
                    'terminated',
                ]),
            ],

            'notes' => ['nullable', 'string'],
        ];
    }
}