<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
}