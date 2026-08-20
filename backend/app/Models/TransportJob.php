<?php

namespace App\Models;

use App\Enums\JobStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'financially_locked_at',
        'remarks',
        'internal_notes',
    ];

    protected $casts = [
        'job_date' => 'date',
        'status' => JobStatus::class,
        'financially_locked_at' => 'datetime',
    ];

    /**
     * Internal notes are hidden by default so a job that is serialised
     * incidentally — nested inside an estimate on GET /estimates, say — cannot
     * carry them out to a quotation. TransportJobResource, which serves the
     * job page, adds the field back explicitly.
     */
    protected $hidden = [
        'internal_notes',
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

    public function activities()
    {
        return $this->hasMany(TransportJobActivity::class, 'job_id');
    }

    public function allocations(): HasMany { return $this->hasMany(InvestmentAllocation::class, 'transport_job_id'); }
    public function profitDistributions(): HasMany { return $this->hasMany(InvestorProfitDistribution::class, 'transport_job_id'); }
    public function financialAdjustments(): HasMany { return $this->hasMany(FinancialAdjustment::class, 'transport_job_id'); }
    public function investors(): BelongsToMany
    {
        return $this->belongsToMany(Investor::class, 'investment_allocations', 'transport_job_id', 'investment_id')
            ->join('investments', 'investments.id', '=', 'investment_allocations.investment_id')
            ->select('investors.*')->distinct();
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
