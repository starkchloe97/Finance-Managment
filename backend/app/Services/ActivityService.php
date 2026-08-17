<?php

namespace App\Services;

use App\Enums\ActivityEvent;
use App\Models\TransportJob;
use App\Models\TransportJobActivity;

/**
 * Writes the job's timeline.
 *
 * Every service that changes a job calls log() once, at the point the change
 * is actually made, so the timeline records what happened rather than what was
 * requested. Nothing reads back from here to make a decision — it is an audit
 * trail, not state.
 */
class ActivityService
{
    public function log(
        TransportJob $job,
        ActivityEvent $event,
        string $description,
        ?array $old = null,
        ?array $new = null
    ): TransportJobActivity {
        return TransportJobActivity::create([
            'job_id' => $job->id,
            'event_type' => $event,
            'description' => $description,
            'old_value' => $old,
            'new_value' => $new,
            // Null when the change came from a seeder or console command,
            // which have no signed-in user to credit.
            'created_by' => auth()->id(),
        ]);
    }
}
