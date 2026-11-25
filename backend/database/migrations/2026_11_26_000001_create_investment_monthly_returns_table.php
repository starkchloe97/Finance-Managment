<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investment_monthly_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('investment_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('installment_number');
            $table->date('due_date');
            $table->decimal('amount', 15, 2);
            $table->decimal('paid_amount', 15, 2)->nullable();
            $table->string('status')->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['investment_id', 'installment_number'], 'imr_investment_installment_unique');
            $table->index(['investment_id', 'status', 'due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investment_monthly_returns');
    }
};
