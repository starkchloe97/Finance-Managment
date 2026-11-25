<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EstimateItem extends Model
{
    protected $fillable = [
        'estimate_id',
        'title',
        'category',
        'quantity',
        'cost_price',
        'sell_price',
        'parts_cost',
        'labor_cost',
        'cost_total',
        'sell_total',
        'profit',
        'remarks',
    ];

    protected $casts = [
        'parts_cost' => 'decimal:2',
        'labor_cost' => 'decimal:2',
    ];

    public function estimate()
    {
        return $this->belongsTo(Estimate::class);
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(EstimateItemVehicle::class);
    }
}
