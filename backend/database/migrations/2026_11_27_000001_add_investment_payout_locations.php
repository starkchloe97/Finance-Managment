<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('investment_monthly_returns', function (Blueprint $table) {
            $table->enum('payout_location', ['hand', 'bank'])->nullable()->after('paid_at');
            $table->foreignId('bank_account_id')->nullable()->after('payout_location')->constrained()->nullOnDelete();
        });

        Schema::table('investment_settlements', function (Blueprint $table) {
            $table->enum('payout_location', ['hand', 'bank'])->nullable()->after('actual_settlement_amount');
            $table->foreignId('bank_account_id')->nullable()->after('payout_location')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('investment_monthly_returns', function (Blueprint $table) {
            $table->dropConstrainedForeignId('bank_account_id');
            $table->dropColumn('payout_location');
        });

        Schema::table('investment_settlements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('bank_account_id');
            $table->dropColumn('payout_location');
        });
    }
};
