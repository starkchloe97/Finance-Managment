<?php

namespace App\Enums;

enum InvestmentReturnType: string
{
    case FixedRate = 'fixed_rate';
    case ProfitShare = 'profit_share';
}
