<?php

namespace App\Models;

use App\Enums\AllocationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvestmentAllocation extends Model
{
    protected $fillable = ['investment_id', 'transport_job_id', 'amount', 'status', 'allocated_at', 'notes'];

    protected $casts = [
        'amount' => 'decimal:2',
        'status' => AllocationStatus::class,
        'allocated_at' => 'datetime',
    ];

    public function investment(): BelongsTo { return $this->belongsTo(Investment::class); }
    public function transportJob(): BelongsTo { return $this->belongsTo(TransportJob::class); }
}
