<?php

namespace App\Models;

use App\Enums\InvestmentCategory;
use App\Enums\InvestmentReturnType;
use App\Enums\InvestmentStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Investment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'investment_code',
        'investor_id',
        'investment_date',
        'amount',
        'funds_location',
        'bank_account_id',
        'investment_category',
        'return_type',
        'return_percentage',
        'fixed_return_amount',
        'period_months',
        'return_policy_days',
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
        'investment_category' => InvestmentCategory::class,
        'return_type' => InvestmentReturnType::class,
        'return_percentage' => 'decimal:2',
        'fixed_return_amount' => 'decimal:2',
        'deduction_amount' => 'decimal:2',
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

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function receipt(): MorphOne
    {
        return $this->morphOne(TransactionReceipt::class, 'receiptable');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(InvestmentAllocation::class);
    }

    public function profitDistributions(): HasMany
    {
        return $this->hasMany(InvestorProfitDistribution::class);
    }

    public function settlements(): HasMany
    {
        return $this->hasMany(InvestmentSettlement::class);
    }

    public function monthlyReturns(): HasMany
    {
        return $this->hasMany(InvestmentMonthlyReturn::class);
    }

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

    public function getCalculatedReturnAmountAttribute(): float
    {
        if ($this->return_type === InvestmentReturnType::Fixed) {
            return round((float) ($this->fixed_return_amount ?? 0), 2);
        }

        return round(
            (float) $this->amount * ((float) ($this->return_percentage ?? 0) / 100),
            2
        );
    }

    public function getExpectedSettlementAmountAttribute(): float
    {
        if ($this->monthlyReturns()->exists()) {
            return round((float) $this->amount - (float) ($this->deduction_amount ?? 0), 2);
        }

        return round(
            (float) $this->amount + $this->calculated_return_amount - (float) ($this->deduction_amount ?? 0),
            2
        );
    }

    public function getTotalScheduledReturnAmountAttribute(): float
    {
        return round((float) $this->calculated_return_amount * (int) $this->period_months, 2);
    }

    public function getTotalPaidMonthlyReturnAmountAttribute(): float
    {
        return round((float) $this->monthlyReturns()->where('status', 'paid')->sum('paid_amount'), 2);
    }
}
