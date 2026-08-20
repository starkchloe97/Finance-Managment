<?php

namespace App\Enums;

enum AllocationStatus: string
{
    case Active = 'active';
    case Released = 'released';
    case Cancelled = 'cancelled';
}
