<?php

namespace App\Enums;

/**
 * What kind of unexpected cost this was.
 *
 * A fixed list rather than a table, because nothing hangs off a category yet —
 * it is grouped for display and nothing more. Keeping the list in one place is
 * what makes promoting it to a table later a small change: the column is a
 * plain string, so that move needs no migration of the storage type.
 */
enum ExpenseCategory: string
{
    case Fuel = 'fuel';

    case Repair = 'repair';

    case Toll = 'toll';

    case Parking = 'parking';

    case Fine = 'fine';

    case DriverAdvance = 'driver_advance';

    case Accommodation = 'accommodation';

    case LoadingUnloading = 'loading_unloading';

    case Permit = 'permit';

    case Miscellaneous = 'miscellaneous';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
