<?php

namespace App\Enums;

enum InvoiceDirection: string
{
    case Receivable = 'receivable';
    case Payable = 'payable';
}
