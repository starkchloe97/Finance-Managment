<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EstimateItem extends Model
{
    protected $fillable = [
        'estimate_id',
        'title',
        'category',
        'quantity',
        'unit_price',
        'total',
        'notes'
    ];

    public function estimate()
    {
        return $this->belongsTo(Estimate::class);
    }
}