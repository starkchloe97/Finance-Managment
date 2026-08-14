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
        'sell_price',
        'cost_price',
        'base_profit',
        'extra_costs',
        'final_profit',
        'remarks'
    ];

    protected $casts = [
        'job_date' => 'date'
    ];

    public function estimate()
    {
        return $this->belongsTo(Estimate::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function expenses()
    {
        return $this->hasMany(TransportJobExpense::class, 'job_id');
    }

    /**
     * Re-add the job's profit figures.
     *
     * sell_price and cost_price are agreed up front and are not touched here,
     * so base_profit is the profit the job was taken on. Unexpected costs are
     * summed from the expenses and come straight off it.
     *
     * final_profit is allowed to go negative — that is a real loss on the job,
     * and hiding it behind a zero would be worse than showing it.
     */
    public function recalculate(): void
    {
        $this->extra_costs = $this->expenses()->sum('amount');

        $this->base_profit = $this->sell_price - $this->cost_price;

        $this->final_profit = $this->base_profit - $this->extra_costs;

        $this->save();
    }
}
