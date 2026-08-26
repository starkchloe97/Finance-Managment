<?php

namespace App\Enums;

enum LoanStatus: string
{
    case Active = 'active';
    case Overdue = 'overdue';
    case Paid = 'paid';
    case Cancelled = 'cancelled';
}
