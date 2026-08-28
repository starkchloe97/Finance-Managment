<?php

namespace App\Enums;

enum CompanyCapitalDraftStatus: string
{
    case Draft = 'draft';
    case Converted = 'converted';
    case Removed = 'removed';
}
