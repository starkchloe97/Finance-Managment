<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->string('loan_code')->unique();
            $table->string('borrower_type');
            $table->foreignId('investor_id')->nullable()->constrained('investors')->restrictOnDelete();
            $table->foreignId('loan_borrower_id')->nullable()->constrained('loan_borrowers')->restrictOnDelete();
            $table->decimal('amount', 15, 2);
            $table->date('loan_date');
            $table->date('due_date');
            $table->string('status')->default('active');
            $table->timestamp('first_overdue_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['status', 'due_date']);
            $table->index('investor_id');
            $table->index('loan_borrower_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
