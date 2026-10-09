<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('job_expense_id')
                ->nullable()
                ->unique()
                ->constrained('job_expenses')
                ->nullOnDelete();
            $table->foreignId('vehicle_contract_id')
                ->nullable()
                ->constrained('vehicle_contracts')
                ->nullOnDelete();
            $table->date('billing_period')->nullable();
            $table->unique(['vehicle_contract_id', 'billing_period']);
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropUnique(['vehicle_contract_id', 'billing_period']);
            $table->dropConstrainedForeignId('vehicle_contract_id');
            $table->dropConstrainedForeignId('job_expense_id');
            $table->dropColumn('billing_period');
        });
    }
};
