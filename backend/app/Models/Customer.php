<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'phone',
        'email',
        'company',
        'address',
        'notes',
    ];

    public function jobs()
    {
        return $this->hasMany(TransportJob::class);
    }

    public function estimates()
    {
        return $this->hasMany(Estimate::class);
    }

    public function activities()
    {
        return $this->hasMany(TransportJobActivity::class, 'job_id');
    }

    /**
     * Aggregates over the customer's jobs. Each is a stored column on the job —
     * never recomputed here — so these read the same figures the job page shows.
     * Planned cost is the cost price agreed at quotation; the actual cost adds
     * the unexpected expenses.
     */
    protected function jobsSummary(): Attribute
    {
        return Attribute::get(function () {
            $jobs = $this->jobs()->with('expenses')->get();

            $revenue = $jobs->sum('sell_price');
            $plannedCost = $jobs->sum('cost_price');
            $extraCosts = $jobs->sum('extra_costs');
            $profit = $jobs->sum('final_profit');

            return [
                'job_count' => $jobs->count(),
                'revenue' => round($revenue, 2),
                'cost' => round($plannedCost + $extraCosts, 2),
                'profit' => round($profit, 2),
            ];
        });
    }

    protected function estimateCount(): Attribute
    {
        return Attribute::get(fn () => $this->estimates()->count());
    }
}
