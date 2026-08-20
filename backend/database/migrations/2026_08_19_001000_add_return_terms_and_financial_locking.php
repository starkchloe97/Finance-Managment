<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('investments', function (Blueprint $table) {
            $table->enum('return_type', ['fixed_rate', 'profit_share'])->default('profit_share')->after('amount');
            $table->decimal('return_rate', 12, 2)->default(0)->after('return_type');
        });

        Schema::table('transport_jobs', function (Blueprint $table) {
            $table->timestamp('financially_locked_at')->nullable()->after('final_profit');
        });
    }

    public function down(): void
    {
        Schema::table('transport_jobs', function (Blueprint $table) {
            $table->dropColumn('financially_locked_at');
        });

        Schema::table('investments', function (Blueprint $table) {
            $table->dropColumn(['return_type', 'return_rate']);
        });
    }
};
