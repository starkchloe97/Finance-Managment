<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Investor extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'investor_code',
        'name',
        'email',
        'phone',
        'address',
        'status',
        'notes',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    public function investments(): HasMany
    {
        return $this->hasMany(Investment::class);
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    public function profitDistributions(): HasMany
    {
        return $this->hasMany(InvestorProfitDistribution::class);
    }
}
