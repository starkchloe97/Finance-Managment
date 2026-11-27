<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaction_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_no')->unique();
            $table->string('receipt_type', 50);
            $table->enum('direction', ['received', 'paid']);
            $table->decimal('amount', 15, 2);
            $table->date('receipt_date');
            $table->string('party_name')->nullable();
            $table->string('description');
            $table->text('notes')->nullable();
            $table->nullableMorphs('receiptable');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['receiptable_type', 'receiptable_id'], 'receipt_source_unique');
            $table->index(['receipt_date', 'direction']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_receipts');
    }
};
