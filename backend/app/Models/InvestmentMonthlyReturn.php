<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvestmentMonthlyReturn extends Model
{
    use HasFactory;

    protected $fillable = [
        'investment_id',
        'installment_number',
        'due_date',
        'amount',
        'paid_amount',
        'status',
        'paid_at',
        'notes',
    ];

    protected $casts = [
        'installment_number' => 'integer',
        'due_date' => 'date',
        'amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function investment(): BelongsTo
    {
        return $this->belongsTo(Investment::class);
    }
}
