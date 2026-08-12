<?php

namespace App\Helpers;

class NumberGenerator
{
    public static function generate(string $prefix, string $model): string
    {
        $last = $model::withoutGlobalScopes()->latest('id')->first();

        $number = $last ? $last->id + 1 : 1;

        return $prefix . '-' . str_pad($number, 6, '0', STR_PAD_LEFT);
    }
}