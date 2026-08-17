<?php

namespace App\Models;

use App\Enums\ExpenseCategory;
use Illuminate\Database\Eloquent\Model;

class TransportJobExpense extends Model
{
    protected $table = 'job_expenses';

    protected $fillable = [
        'job_id',
        'title',
        'category',
        'amount',
        'expense_date',
        'notes',
    ];

    protected $casts = [
        'expense_date' => 'date',
        'amount' => 'decimal:2',
        'category' => ExpenseCategory::class,
    ];

    public function transportJob()
    {
        return $this->belongsTo(TransportJob::class, 'job_id');
    }
}
