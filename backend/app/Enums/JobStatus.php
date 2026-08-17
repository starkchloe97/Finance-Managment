<?php

namespace App\Enums;

/**
 * The stages a job passes through, in the order it passes through them.
 *
 * The value is a record of how far the work has actually got, so movement is
 * forward only — a stage cannot be skipped and nothing can be walked back. The
 * map of which stage may follow which lives in TransportJobService, which is
 * the only place a job's status is allowed to change.
 */
enum JobStatus: string
{
    case Draft = 'draft';

    case Confirmed = 'confirmed';

    case Assigned = 'assigned';

    case InTransit = 'in_transit';

    case Delivered = 'delivered';

    case Completed = 'completed';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
