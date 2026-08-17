<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The old status list described where a job sat in the money flow (budgeted,
 * funded) — but budgeting was folded into the estimate and funding never
 * arrived, so most of those values could never be reached. It is replaced by
 * the stages the work itself moves through.
 *
 * Neither driver will accept a value its column does not yet allow, so in both
 * cases the column is widened to a plain string, the existing rows are
 * remapped, and the new list is applied over the top. The app runs on MySQL
 * and the test suite on sqlite, which has no enum type of its own — it stores
 * one as a varchar with a check constraint — so the two need different tools
 * for the same three steps.
 */
return new class extends Migration
{
    private const NEW_VALUES = [
        'draft',
        'confirmed',
        'assigned',
        'in_transit',
        'delivered',
        'completed',
    ];

    private const OLD_VALUES = [
        'draft',
        'ready',
        'budgeted',
        'funded',
        'in_progress',
        'completed',
        'closed',
        'cancelled',
    ];

    /**
     * 'cancelled' has no counterpart — the new list has no way to abandon a
     * job — so a cancelled job goes back to 'draft' rather than being recorded
     * as work that was carried out.
     */
    private const UP_MAP = [
        'ready' => 'confirmed',
        'budgeted' => 'confirmed',
        'funded' => 'assigned',
        'in_progress' => 'in_transit',
        'closed' => 'completed',
        'cancelled' => 'draft',
    ];

    private const DOWN_MAP = [
        'confirmed' => 'ready',
        'assigned' => 'funded',
        'in_transit' => 'in_progress',
        'delivered' => 'in_progress',
    ];

    public function up(): void
    {
        $this->remap(self::UP_MAP, self::NEW_VALUES);
    }

    public function down(): void
    {
        $this->remap(self::DOWN_MAP, self::OLD_VALUES);
    }

    private function remap(array $map, array $target): void
    {
        $mysql = in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true);

        if ($mysql) {
            DB::statement("ALTER TABLE transport_jobs MODIFY status VARCHAR(20) NOT NULL DEFAULT 'draft'");
        } else {
            Schema::table('transport_jobs', function (Blueprint $table) {
                $table->string('status', 20)->default('draft')->change();
            });
        }

        foreach ($map as $from => $to) {
            DB::table('transport_jobs')->where('status', $from)->update(['status' => $to]);
        }

        if ($mysql) {
            $list = implode(',', array_map(fn ($value) => "'{$value}'", $target));

            DB::statement("ALTER TABLE transport_jobs MODIFY status ENUM({$list}) NOT NULL DEFAULT 'draft'");

            return;
        }

        Schema::table('transport_jobs', function (Blueprint $table) use ($target) {
            $table->enum('status', $target)->default('draft')->change();
        });
    }
};
