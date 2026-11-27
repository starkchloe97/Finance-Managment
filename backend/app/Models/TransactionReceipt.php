<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class TransactionReceipt extends Model
{
    protected $fillable = [
        'receipt_no',
        'receipt_type',
        'direction',
        'amount',
        'receipt_date',
        'party_name',
        'description',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'receipt_date' => 'date',
    ];

    public function receiptable(): MorphTo
    {
        return $this->morphTo();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
