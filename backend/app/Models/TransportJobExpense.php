<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobExpense extends Model
{
    public function transportJob()
{
    return $this->belongsTo(TransportJob::class);
}
}
