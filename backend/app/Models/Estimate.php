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
        'estimate_type',
        'estimate_date',
        'valid_until',
        'pickup',
        'destination',
        'service_type',
        'tonnage',
        'details',
        'terms',
        'payment_terms',
        'tax_rate',
        'tax_amount',
        'grand_total',
        'prepared_by',
        'checked_by',
        'approved_by',
        'customer_approval',
        'estimated_cost',
        'estimated_sell',
        'estimated_profit',
        'status',
        'remarks',
    ];

    protected $casts = [
        'estimate_date' => 'date',
        'valid_until' => 'date',
        'tonnage' => 'decimal:3',
        'details' => 'array',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'grand_total' => 'decimal:2',
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
