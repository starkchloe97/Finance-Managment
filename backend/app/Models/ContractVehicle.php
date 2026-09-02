<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContractVehicle extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_contract_id',

        'vehicle_number',

        'make',
        'model',
        'model_year',
        'vehicle_type',

        'monthly_rental',

        'duty_hours_per_day',
        'duty_days_per_week',

        'public_holiday_rate',
        'overtime_rate',

        'monthly_mileage_limit',
        'excess_mileage_rate',

        'status',
        'notes',
    ];

    protected $casts = [
        'monthly_rental' => 'decimal:2',
        'public_holiday_rate' => 'decimal:2',
        'overtime_rate' => 'decimal:2',
        'excess_mileage_rate' => 'decimal:2',

        'duty_hours_per_day' => 'integer',
        'duty_days_per_week' => 'integer',
        'monthly_mileage_limit' => 'integer',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(
            VehicleContract::class,
            'vehicle_contract_id'
        );
    }

    public function dailyReports(): HasMany
    {
        return $this->hasMany(
            VehicleDailyReport::class
        );
    }
}
