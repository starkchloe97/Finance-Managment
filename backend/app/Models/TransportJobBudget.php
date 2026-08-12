<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransportJobBudget extends Model
{
    protected $table = 'job_budget_items';

    protected $fillable = [
        'job_id',
        'title',
        'category',
        'quantity',
        'unit_cost',
        'total',
        'notes'
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'total' => 'decimal:2'
    ];

    public function transportJob()
    {
        return $this->belongsTo(TransportJob::class, 'job_id');
    }
}
