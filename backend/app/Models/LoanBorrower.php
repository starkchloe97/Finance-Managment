<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoanBorrower extends Model
{
    protected $fillable = ['borrower_code', 'name', 'email', 'phone', 'address', 'notes'];

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }
}
