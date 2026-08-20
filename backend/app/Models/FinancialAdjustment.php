<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancialAdjustment extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'transport_job_id',
        'field',
        'old_value',
        'new_value',
        'reason',
        'user_id',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function transportJob(): BelongsTo
    {
        return $this->belongsTo(TransportJob::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
