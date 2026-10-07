<?php

namespace App\Models;

use App\Enums\PaymentDirection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    protected $fillable = ['payment_no', 'direction', 'payment_date', 'amount', 'payment_method', 'reference', 'notes', 'created_by'];

    protected $casts = [
        'direction' => PaymentDirection::class,
        'payment_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function allocations(): HasMany
    {
        return $this->hasMany(InvoicePayment::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
