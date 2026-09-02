<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EstimateItemVehicle extends Model
{
    protected $fillable = [
        'estimate_item_id',
        'source',
        'asset_id',
        'vehicle_name',
        'make',
        'model',
        'model_year',
        'registration_number',
        'vin',
        'engine_number',
        'vehicle_type',
        'color',
        'notes',
    ];

    public function estimateItem(): BelongsTo
    {
        return $this->belongsTo(EstimateItem::class);
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }
}
