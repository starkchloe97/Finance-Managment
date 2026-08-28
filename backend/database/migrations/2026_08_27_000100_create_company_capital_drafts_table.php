<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_capital_drafts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_capital_account_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 15, 2);
            $table->date('transaction_date');
            $table->string('note')->nullable();
            $table->string('status')->default('draft');
            $table->timestamp('removed_at')->nullable();
            $table->string('removal_note')->nullable();
            $table->foreignId('removed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('company_capital_transaction_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_capital_drafts');
    }
};
