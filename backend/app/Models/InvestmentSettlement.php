<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvestmentSettlement extends Model
{
    public $timestamps = false;
    protected $fillable = ['investment_id', 'principal_amount', 'distribution_amount', 'deduction_amount', 'actual_settlement_amount', 'calculated_at'];
    protected $casts = ['principal_amount' => 'decimal:2', 'distribution_amount' => 'decimal:2', 'deduction_amount' => 'decimal:2', 'actual_settlement_amount' => 'decimal:2', 'calculated_at' => 'datetime'];
    public function investment(): BelongsTo { return $this->belongsTo(Investment::class); }
}
