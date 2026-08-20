<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investment_settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('investment_id')->constrained()->restrictOnDelete();
            $table->decimal('principal_amount', 12, 2);
            $table->decimal('distribution_amount', 12, 2);
            $table->decimal('deduction_amount', 12, 2);
            $table->decimal('actual_settlement_amount', 12, 2);
            $table->timestamp('calculated_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investment_settlements');
    }
};
