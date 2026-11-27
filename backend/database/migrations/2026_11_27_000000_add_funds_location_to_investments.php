<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('investments', function (Blueprint $table) {
            $table->enum('funds_location', ['hand', 'bank'])
                ->default('hand')
                ->after('amount');
            $table->foreignId('bank_account_id')
                ->nullable()
                ->after('funds_location')
                ->constrained()
                ->nullOnDelete();
        });

        Schema::table('bank_transactions', function (Blueprint $table) {
            $table->foreignId('investment_id')
                ->nullable()
                ->after('payment_id')
                ->constrained()
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('bank_transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('investment_id');
        });

        Schema::table('investments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('bank_account_id');
            $table->dropColumn('funds_location');
        });
    }
};
