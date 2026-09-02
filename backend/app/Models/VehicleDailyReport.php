<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleDailyReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_vehicle_id',
        'report_date',

        'time_in',
        'time_out',

        'meter_in',
        'meter_out',

        'fuel_drawn',

        'total_minutes',
        'normal_minutes',
        'overtime_minutes',

        'total_running',
        'overtime_amount',

        'excess_mileage',
        'excess_mileage_amount',

        'is_public_holiday',
        'is_weekly_off',

        'status',
        'remarks',
    ];

    protected $casts = [
        'report_date' => 'date',

        'meter_in' => 'decimal:2',
        'meter_out' => 'decimal:2',

        'fuel_drawn' => 'decimal:2',

        'total_minutes' => 'integer',
        'normal_minutes' => 'integer',
        'overtime_minutes' => 'integer',

        'total_running' => 'decimal:2',

        'overtime_amount' => 'decimal:2',

        'excess_mileage' => 'decimal:2',
        'excess_mileage_amount' => 'decimal:2',

        'is_public_holiday' => 'boolean',
        'is_weekly_off' => 'boolean',
    ];

    public function contractVehicle(): BelongsTo
    {
        return $this->belongsTo(
            ContractVehicle::class
        );
    }
}