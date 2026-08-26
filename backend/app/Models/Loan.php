<?php

namespace App\Models;

use App\Enums\LoanBorrowerType;
use App\Enums\LoanStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Loan extends Model
{
    protected $fillable = [
        'loan_code', 'borrower_type', 'investor_id', 'loan_borrower_id', 'amount', 'loan_date',
        'due_date', 'status', 'first_overdue_at', 'paid_at', 'cancelled_at', 'notes', 'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'borrower_type' => LoanBorrowerType::class,
        'status' => LoanStatus::class,
        'loan_date' => 'date',
        'due_date' => 'date',
        'first_overdue_at' => 'datetime',
        'paid_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function investor(): BelongsTo
    {
        return $this->belongsTo(Investor::class);
    }

    public function borrower(): BelongsTo
    {
        return $this->belongsTo(LoanBorrower::class, 'loan_borrower_id');
    }

    public function repayments(): HasMany
    {
        return $this->hasMany(LoanRepayment::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getTotalRepaidAttribute(): float
    {
        return round((float) ($this->repayments_sum_amount ?? $this->repayments()->sum('amount')), 2);
    }

    public function getOutstandingAmountAttribute(): float
    {
        return max(0, round((float) $this->amount - $this->total_repaid, 2));
    }

    public function getDisplayStatusAttribute(): LoanStatus
    {
        if ($this->status === LoanStatus::Cancelled || $this->status === LoanStatus::Paid) {
            return $this->status;
        }

        return $this->due_date->isBefore(today()) && $this->outstanding_amount > 0
            ? LoanStatus::Overdue
            : LoanStatus::Active;
    }

    public function borrowerName(): string
    {
        return $this->borrower_type === LoanBorrowerType::Investor
            ? $this->investor?->name ?? 'Unknown investor'
            : $this->borrower?->name ?? 'Unknown borrower';
    }
}
