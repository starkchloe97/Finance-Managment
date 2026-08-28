<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyCapitalDraftActivity extends Model
{
    protected $fillable = [
        'company_capital_draft_id',
        'activity_type',
        'note',
        'metadata',
        'created_by',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function draft(): BelongsTo
    {
        return $this->belongsTo(CompanyCapitalDraft::class, 'company_capital_draft_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
