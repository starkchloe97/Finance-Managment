<?php

namespace App\Enums;

enum InvestmentStatus: string
{
    case Active = 'active';
    case Matured = 'matured';
    case Withdrawn = 'withdrawn';
    case Settled = 'settled';
    case Cancelled = 'cancelled';
}
