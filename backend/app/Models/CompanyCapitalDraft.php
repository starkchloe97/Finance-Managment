<?php

namespace App\Models;

use App\Enums\CompanyCapitalDraftStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CompanyCapitalDraft extends Model
{
    protected $fillable = [
        'company_capital_account_id', 'amount', 'transaction_date', 'note',
        'status', 'removed_at', 'removal_note', 'removed_by',
        'company_capital_transaction_id', 'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'status' => CompanyCapitalDraftStatus::class,
        'transaction_date' => 'date',
        'removed_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function remover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'removed_by');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(CompanyCapitalTransaction::class, 'company_capital_transaction_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(CompanyCapitalAccount::class, 'company_capital_account_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(CompanyCapitalDraftActivity::class, 'company_capital_draft_id');
    }
}
