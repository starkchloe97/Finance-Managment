<?php

namespace App\Enums;

enum InvoiceCategory: string
{
    case Job = 'job';
    case VehicleRental = 'vehicle_rental';
    case Sales = 'sales';
    case Commission = 'commission';
    case Contractor = 'contractor';
    case Supplier = 'supplier';
    case Administrative = 'administrative';
    case Marketing = 'marketing';
    case Other = 'other';
}
