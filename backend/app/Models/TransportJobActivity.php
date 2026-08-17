<?php

namespace App\Models;

use App\Enums\ActivityEvent;
use Illuminate\Database\Eloquent\Model;

/**
 * One line of a job's history. Append-only — there is no updated_at because a
 * row is never touched again once written.
 */
class TransportJobActivity extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'job_id',
        'event_type',
        'description',
        'old_value',
        'new_value',
        'created_by',
    ];

    protected $casts = [
        'event_type' => ActivityEvent::class,
        'old_value' => 'array',
        'new_value' => 'array',
    ];

    public function transportJob()
    {
        return $this->belongsTo(TransportJob::class, 'job_id');
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
