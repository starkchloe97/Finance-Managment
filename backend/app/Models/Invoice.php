<?php

namespace App\Models;

use App\Enums\InvoiceDirection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    protected $fillable = [
        'invoice_no', 'direction', 'category', 'customer_id', 'transport_job_id',
        'party_name', 'party_company', 'party_contact', 'party_phone', 'party_address', 'party_tax_number',
        'company_name', 'company_phone', 'company_address', 'company_tax_number',
        'invoice_date', 'due_date', 'subtotal', 'tax_amount', 'tax_label', 'tax_number',
        'total', 'notes', 'created_by',
    ];

    protected $casts = [
        'direction' => InvoiceDirection::class,
        'invoice_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function transportJob(): BelongsTo
    {
        return $this->belongsTo(TransportJob::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(InvoicePayment::class);
    }

    public function getPaidAmountAttribute(): float
    {
        return round((float) ($this->paid_amount_sum ?? $this->allocations()->sum('amount')), 2);
    }

    public function getOutstandingAmountAttribute(): float
    {
        return round(max(0, (float) $this->total - $this->paid_amount), 2);
    }

    public function getStatusAttribute(): string
    {
        if ($this->outstanding_amount <= 0) {
            return 'paid';
        }
        if ($this->due_date?->isBefore(today())) {
            return 'overdue';
        }
        if ($this->paid_amount > 0) {
            return 'partially_paid';
        }

        return 'unpaid';
    }
}
