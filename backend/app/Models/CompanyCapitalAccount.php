<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CompanyCapitalAccount extends Model
{
    protected $fillable = ['code', 'name', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function transactions(): HasMany
    {
        return $this->hasMany(CompanyCapitalTransaction::class);
    }
}
