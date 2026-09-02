<?php

namespace App\Enums;

enum AssetType: string
{
    case Vehicle = 'vehicle';

    public function label(): string
    {
        return match ($this) {
            self::Vehicle => 'Vehicle',
        };
    }
}
