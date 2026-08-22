<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('investments', function (Blueprint $table) {
            $table->dropColumn([
                'min_return_percent',
                'max_return_percent',
                'return_rate',
                'legacy_return_type',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('investments', function (Blueprint $table) {
            $table->decimal('min_return_percent', 5, 2)->nullable();
            $table->decimal('max_return_percent', 5, 2)->nullable();
            $table->decimal('return_rate', 12, 2)->nullable();
            $table->enum('legacy_return_type', ['fixed_rate', 'profit_share'])->nullable();
        });

        DB::table('investments')
            ->orderBy('id')
            ->chunkById(100, function ($investments): void {
                foreach ($investments as $investment) {
                    if ($investment->return_type === 'fixed') {
                        $values = [
                            'legacy_return_type' => 'fixed_rate',
                            'return_rate' => $investment->fixed_return_amount ?? 0,
                            'min_return_percent' => null,
                            'max_return_percent' => null,
                        ];
                    } else {
                        $values = [
                            'legacy_return_type' => 'profit_share',
                            'return_rate' => $investment->return_percentage ?? 0,
                            'min_return_percent' => $investment->return_percentage ?? 0,
                            'max_return_percent' => $investment->return_percentage ?? 0,
                        ];
                    }

                    DB::table('investments')
                        ->where('id', $investment->id)
                        ->update($values);
                }
            });
    }
};
