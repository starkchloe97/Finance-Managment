<?php

namespace App\Enums;

enum LoanBorrowerType: string
{
    case Investor = 'investor';
    case Outsider = 'outsider';
}
