<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Estimate extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'customer_id',
        'estimate_date',
        'valid_until',
        'pickup',
        'destination',
        'service_type',
        'estimated_cost',
        'estimated_sell',
        'estimated_profit',
        'status',
        'remarks',
    ];

    protected $casts = [
        'estimate_date'=>'date',
        'valid_until'=>'date'
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(EstimateItem::class);
    }
    public function transportJob()
    {
        return $this->hasOne(TransportJob::class);
    }
}