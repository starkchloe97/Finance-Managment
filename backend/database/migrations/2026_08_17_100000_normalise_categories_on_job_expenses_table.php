<?php

use App\Enums\ExpenseCategory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Categories used to be free text, and are now a fixed list cast to
 * ExpenseCategory. An unrecognised value in the column would make the model
 * throw on read, so existing rows are brought onto the list first.
 *
 * The column itself stays a plain string: the list is enforced in PHP, which
 * is what lets a category be added without a migration.
 */
return new class extends Migration
{
    /**
     * Words that were plausibly in use before the list existed. Anything not
     * recognised falls back to miscellaneous — a wrong-but-visible category is
     * better than a row that cannot be loaded.
     */
    private const ALIASES = [
        'breakdown' => 'repair',
        'maintenance' => 'repair',
        'diesel' => 'fuel',
        'petrol' => 'fuel',
        'labor' => 'loading_unloading',
        'labour' => 'loading_unloading',
        'loading' => 'loading_unloading',
        'unloading' => 'loading_unloading',
        'penalty' => 'fine',
        'challan' => 'fine',
        'advance' => 'driver_advance',
        'food' => 'accommodation',
        'lodging' => 'accommodation',
        'other' => 'miscellaneous',
    ];

    public function up(): void
    {
        $valid = ExpenseCategory::values();

        DB::table('job_expenses')->select('id', 'category')->orderBy('id')->chunk(200, function ($rows) use ($valid) {
            foreach ($rows as $row) {

                $slug = Str::snake(Str::lower(trim((string) $row->category)));

                $slug = self::ALIASES[$slug] ?? $slug;

                $category = in_array($slug, $valid, true) ? $slug : 'miscellaneous';

                if ($category !== $row->category) {
                    DB::table('job_expenses')->where('id', $row->id)->update(['category' => $category]);
                }
            }
        });
    }

    /**
     * Not reversible in any meaningful sense — the free text that was there
     * before is gone, and there is nothing to restore it from.
     */
    public function down(): void
    {
        //
    }
};
