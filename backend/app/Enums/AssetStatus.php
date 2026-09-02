<?php

namespace App\Enums;

enum AssetStatus: string
{
    case Active = 'active';
    case Maintenance = 'maintenance';
    case Inactive = 'inactive';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Maintenance => 'Maintenance',
            self::Inactive => 'Inactive',
        };
    }
}
