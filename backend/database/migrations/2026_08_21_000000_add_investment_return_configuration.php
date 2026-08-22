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
            $table->renameColumn('return_type', 'legacy_return_type');
        });

        Schema::table('investments', function (Blueprint $table) {
            $table->enum('investment_category', ['pool', 'normal'])
                ->default('normal')
                ->after('amount');
            $table->enum('return_type', ['fixed', 'percentage'])
                ->default('percentage')
                ->after('investment_category');
            $table->decimal('return_percentage', 5, 2)
                ->nullable()
                ->after('return_type');
            $table->decimal('fixed_return_amount', 15, 2)
                ->nullable()
                ->after('return_percentage');
        });

        DB::table('investments')
            ->orderBy('id')
            ->chunkById(100, function ($investments): void {
                foreach ($investments as $investment) {
                    $percentage = $investment->max_return_percent
                        ?? $investment->min_return_percent;

                    if ($percentage !== null) {
                        $values = [
                            'return_type' => 'percentage',
                            'return_percentage' => $percentage,
                            'fixed_return_amount' => null,
                        ];
                    } elseif ($investment->legacy_return_type === 'fixed_rate') {
                        $values = [
                            'return_type' => 'fixed',
                            'return_percentage' => null,
                            'fixed_return_amount' => $investment->return_rate ?? 0,
                        ];
                    } else {
                        $values = [
                            'return_type' => 'percentage',
                            'return_percentage' => $investment->return_rate ?? 0,
                            'fixed_return_amount' => null,
                        ];
                    }

                    DB::table('investments')
                        ->where('id', $investment->id)
                        ->update([
                            'investment_category' => 'normal',
                            ...$values,
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('investments', function (Blueprint $table) {
            $table->dropColumn([
                'investment_category',
                'return_type',
                'return_percentage',
                'fixed_return_amount',
            ]);
        });

        Schema::table('investments', function (Blueprint $table) {
            $table->renameColumn('legacy_return_type', 'return_type');
        });
    }
};
