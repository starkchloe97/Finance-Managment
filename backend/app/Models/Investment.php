<?php

namespace App\Models;

use App\Enums\InvestmentStatus;
use App\Enums\InvestmentReturnType;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Investment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'investment_code',
        'investor_id',
        'investment_date',
        'amount',
        'return_type',
        'return_rate',
        'period_months',
        'return_policy_days',
        'min_return_percent',
        'max_return_percent',
        'status',
        'matured_at',
        'withdrawn_at',
        'deduction_amount',
        'notes',
        'settled_at',
        'cancelled_at',
    ];

    protected $casts = [
        'investment_date' => 'date',
        'amount' => 'decimal:2',
        'return_type' => InvestmentReturnType::class,
        'return_rate' => 'decimal:2',
        'deduction_amount' => 'decimal:2',
        'min_return_percent' => 'decimal:2',
        'max_return_percent' => 'decimal:2',
        'status' => InvestmentStatus::class,
        'matured_at' => 'datetime',
        'withdrawn_at' => 'datetime',
        'settled_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function investor(): BelongsTo
    {
        return $this->belongsTo(Investor::class);
    }

    public function allocations(): HasMany { return $this->hasMany(InvestmentAllocation::class); }
    public function profitDistributions(): HasMany { return $this->hasMany(InvestorProfitDistribution::class); }
    public function settlements(): HasMany { return $this->hasMany(InvestmentSettlement::class); }

    public function getAllocatedAmountAttribute(): float
    {
        return round((float) $this->allocations()->where('status', 'active')->sum('amount'), 2);
    }

    public function getRemainingCapitalAttribute(): float
    {
        return round((float) $this->amount - $this->allocated_amount, 2);
    }

    public function getMaturityDateAttribute(): ?Carbon
    {
        if (! $this->investment_date || ! $this->period_months) {
            return null;
        }

        return Carbon::parse($this->investment_date)->addMonths($this->period_months);
    }

    public function getMinimumReturnAmountAttribute(): float
    {
        if ($this->min_return_percent === null) {
            return 0;
        }

        return round((float) $this->amount * ((float) $this->min_return_percent / 100), 2);
    }

    public function getMaximumReturnAmountAttribute(): float
    {
        if ($this->max_return_percent === null) {
            return 0;
        }

        return round((float) $this->amount * ((float) $this->max_return_percent / 100), 2);
    }

    public function getMinimumSettlementAmountAttribute(): float
    {
        return round(
            (float) $this->amount + $this->minimum_return_amount - (float) ($this->deduction_amount ?? 0),
            2
        );
    }

    public function getMaximumSettlementAmountAttribute(): float
    {
        return round(
            (float) $this->amount + $this->maximum_return_amount - (float) ($this->deduction_amount ?? 0),
            2
        );
    }
}
