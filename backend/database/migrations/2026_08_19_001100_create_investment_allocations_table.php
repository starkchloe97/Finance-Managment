<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('investment_id')->constrained()->restrictOnDelete();
            $table->foreignId('transport_job_id')->constrained('transport_jobs')->restrictOnDelete();
            $table->decimal('amount', 12, 2);
            $table->enum('status', ['active', 'released', 'cancelled'])->default('active');
            $table->timestamp('allocated_at');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['investment_id', 'status']);
            $table->index(['transport_job_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investment_allocations');
    }
};
