<?php

namespace App\Models;

use App\Enums\CompanyCapitalTransactionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CompanyCapitalTransaction extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'company_capital_account_id', 'transaction_code', 'type', 'amount', 'available',
        'transaction_date', 'reference_type', 'reference_id', 'description', 'created_by', 'created_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'available' => 'boolean',
        'type' => CompanyCapitalTransactionType::class,
        'transaction_date' => 'date',
        'created_at' => 'datetime',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(CompanyCapitalAccount::class, 'company_capital_account_id');
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
