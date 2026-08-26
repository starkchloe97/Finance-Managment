<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('company_capital_transactions')) {
            Schema::table('company_capital_transactions', function (Blueprint $table) {
                $table->index(['company_capital_account_id', 'transaction_date'], 'capital_account_date_idx');
            });

            return;
        }

        Schema::create('company_capital_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_capital_account_id')->constrained()->restrictOnDelete();
            $table->string('transaction_code')->unique();
            $table->string('type');
            $table->decimal('amount', 15, 2);
            $table->date('transaction_date');
            $table->nullableMorphs('reference');
            $table->string('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at');
            $table->index(['company_capital_account_id', 'transaction_date'], 'capital_account_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_capital_transactions');
    }
};
