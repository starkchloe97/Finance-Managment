<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('investments', function (Blueprint $table) {
            if (!Schema::hasColumn('investments', 'matured_at')) {
                $table->timestamp('matured_at')->nullable();
            }

            if (!Schema::hasColumn('investments', 'withdrawn_at')) {
                $table->timestamp('withdrawn_at')->nullable();
            }

            if (!Schema::hasColumn('investments', 'settled_at')) {
                $table->timestamp('settled_at')->nullable();
            }

            if (!Schema::hasColumn('investments', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('investments', function (Blueprint $table) {
            $columns = [];

            foreach ([
                'matured_at',
                'withdrawn_at',
                'settled_at',
                'cancelled_at',
            ] as $column) {
                if (Schema::hasColumn('investments', $column)) {
                    $columns[] = $column;
                }
            }

            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};