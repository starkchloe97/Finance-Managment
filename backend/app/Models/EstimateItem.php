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
        'cost_total',
        'sell_total',
        'profit',
        'remarks'
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