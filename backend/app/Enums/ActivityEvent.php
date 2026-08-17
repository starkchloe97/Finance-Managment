<?php

namespace App\Enums;

/**
 * The things that can happen to a job and end up on its timeline.
 *
 * Stored as a plain string rather than a database enum, so a new kind of event
 * needs a case here and a call to ActivityService::log() — not a migration.
 */
enum ActivityEvent: string
{
    case JobCreated = 'job_created';

    case StatusChanged = 'status_changed';

    case CostAdded = 'cost_added';

    case CostUpdated = 'cost_updated';

    case CostDeleted = 'cost_deleted';

    case NotesUpdated = 'notes_updated';
}
