<?php

namespace App\Models;

use App\Enums\AssetStatus;
use App\Enums\AssetType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Asset extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'asset_code',
        'asset_type',
        'name',
        'make',
        'model',
        'model_year',
        'registration_number',
        'vin',
        'engine_number',
        'vehicle_type',
        'color',
        'purchase_date',
        'purchase_price',
        'current_value',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'asset_type' => AssetType::class,
        'status' => AssetStatus::class,

        'model_year' => 'integer',

        'purchase_date' => 'date',

        'purchase_price' => 'decimal:2',
        'current_value' => 'decimal:2',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }
}
