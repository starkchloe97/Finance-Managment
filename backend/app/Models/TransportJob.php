<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransportJob extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'estimate_id',
        'customer_id',
        'job_date',
        'status',
        'quoted_amount',
        'planned_cost',
        'actual_cost',
        'profit',
        'remarks'
    ];

    public function estimate()
    {
        return $this->belongsTo(Estimate::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function budgetItems()
    {
        return $this->hasMany(TransportJobBudget::class);
    }

    public function expenses()
    {
        return $this->hasMany(TransportJobExpense::class);
    }
}