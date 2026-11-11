<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Query extends Model
{
    use SoftDeletes;

    public const STATUSES = [
        'new',
        'contacted',
        'in_progress',
        'resolved',
    ];

    protected $fillable = [
        'name',
        'phone',
        'service',
        'pickup_location',
        'dropoff_location',
        'message',
        'status',
    ];
}
