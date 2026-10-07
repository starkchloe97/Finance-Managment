<?php

namespace App\Enums;

enum PaymentDirection: string
{
    case Received = 'received';
    case Paid = 'paid';
}
