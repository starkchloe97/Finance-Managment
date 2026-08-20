<?php

namespace App\Enums;

enum ProfitDistributionStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
}
