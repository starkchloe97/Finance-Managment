<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VehicleContract extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_number',
        'agreement_date',

        'vendor_name',
        'vendor_address',

        'customer_name',
        'customer_address',
        'customer_tin',

        'end_date',
        'duration_months',

        'service_type',
        'fuel_included',
        'routine_maintenance_included',

        'total_vehicles',
        'vehicle_make',
        'vehicle_model',
        'vehicle_model_year',
        'vehicle_type',

        'monthly_rental_per_vehicle',
        'total_monthly_rental',

        'duty_hours_per_day',
        'duty_days_per_week',
        'public_holiday_rate',
        'overtime_rate',

        'payment_terms',
        'advance_months',

        'insurance_claim_period_days',

        'monthly_mileage_limit',
        'excess_mileage_rate',

        'refrigeration_customer_responsibility',

        'early_termination_months',

        'vendor_signatory_name',
        'vendor_signatory_designation',
        'vendor_signatory_cnic',
        'vendor_signature_date',

        'customer_signatory_name',
        'customer_signatory_designation',
        'customer_signatory_cnic',
        'customer_signature_date',

        'witness_1_name',
        'witness_1_cnic',

        'witness_2_name',
        'witness_2_cnic',

        'status',
        'notes',
    ];

    protected $casts = [
        'agreement_date' => 'date',
        'end_date' => 'date',

        'vendor_signature_date' => 'date',
        'customer_signature_date' => 'date',

        'fuel_included' => 'boolean',
        'routine_maintenance_included' => 'boolean',
        'refrigeration_customer_responsibility' => 'boolean',

        'monthly_rental_per_vehicle' => 'decimal:2',
        'total_monthly_rental' => 'decimal:2',

        'public_holiday_rate' => 'decimal:2',
        'overtime_rate' => 'decimal:2',
        'excess_mileage_rate' => 'decimal:2',

        'total_vehicles' => 'integer',
        'duration_months' => 'integer',
        'duty_hours_per_day' => 'integer',
        'duty_days_per_week' => 'integer',
        'advance_months' => 'integer',
        'insurance_claim_period_days' => 'integer',
        'monthly_mileage_limit' => 'integer',
        'early_termination_months' => 'integer',
    ];
}