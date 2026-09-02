<?php

namespace App\Helpers;

class NumberGenerator
{
    /**
     * Build the next human-readable code for a model, e.g. CUS-000042.
     *
     * Soft-deleted rows still hold their code, so global scopes are dropped
     * here — counting only the visible rows would hand out a code that is
     * already taken and hit the unique index.
     *
     * Usage:
     *   NumberGenerator::generate('CUS', Customer::class)
     *   NumberGenerator::generate('EST', Estimate::class)
     *   NumberGenerator::generate('JOB', TransportJob::class)
     *   NumberGenerator::generate('INV', Investor::class)
     */
    public static function generate(string $prefix, string $model): string
    {
        $number = $model::withoutGlobalScopes()->max('id') + 1;

        return $prefix.'-'.str_pad($number, 6, '0', STR_PAD_LEFT);
    }
}
