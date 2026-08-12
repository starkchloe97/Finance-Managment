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

    public function budgetItems()
    {
        return $this->hasMany(TransportJobBudget::class, 'job_id');
    }

    public function expenses()
    {
        return $this->hasMany(TransportJobExpense::class, 'job_id');
    }

    /**
     * Re-add the job's money columns from its budget lines and expenses.
     * Called whenever either of those changes. quoted_amount is never touched
     * here — that price was promised to the customer.
     */
    public function recalculate(): void
    {
        $this->planned_cost = $this->budgetItems()->sum('total');

        $this->actual_cost = $this->expenses()->sum('amount');

        // Once real expenses exist they decide the profit; before that we go by
        // the budget, which is still only an expectation.
        $cost = $this->actual_cost > 0
            ? $this->actual_cost
            : $this->planned_cost;

        $this->profit = $this->quoted_amount - $cost;

        $this->save();
    }
}