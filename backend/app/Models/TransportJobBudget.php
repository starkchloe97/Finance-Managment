<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransportJobBudget  extends Model
{
   protected $fillable = [
    'job_id',
    'title',
    'category',
    'quantity',
    'unit_cost',
    'total',
    'notes'
];



public function transportJob()
{
    return $this->belongsTo(TransportJob::class);
}
}
