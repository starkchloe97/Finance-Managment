<?php

namespace App\Models;

use App\Enums\ProfitDistributionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvestorProfitDistribution extends Model
{
    protected $fillable = ['investment_id', 'transport_job_id', 'investor_id', 'allocation_id', 'profit_basis', 'profit_share_value', 'profit_amount', 'status', 'distributed_at', 'notes'];

    protected $casts = [
        'profit_basis' => 'decimal:2', 'profit_share_value' => 'decimal:4', 'profit_amount' => 'decimal:2',
        'status' => ProfitDistributionStatus::class, 'distributed_at' => 'datetime',
    ];

    public function investment(): BelongsTo
    {
        return $this->belongsTo(Investment::class);
    }

    public function transportJob(): BelongsTo
    {
        return $this->belongsTo(TransportJob::class);
    }

    public function investor(): BelongsTo
    {
        return $this->belongsTo(Investor::class);
    }

    public function allocation(): BelongsTo
    {
        return $this->belongsTo(InvestmentAllocation::class);
    }
}
