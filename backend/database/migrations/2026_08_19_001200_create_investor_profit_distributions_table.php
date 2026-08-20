<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investor_profit_distributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('investment_id')->constrained()->restrictOnDelete();
            $table->foreignId('transport_job_id')->constrained('transport_jobs')->restrictOnDelete();
            $table->foreignId('investor_id')->constrained()->restrictOnDelete();
            $table->foreignId('allocation_id')->nullable()->constrained('investment_allocations')->nullOnDelete();
            $table->decimal('profit_basis', 12, 2);
            $table->decimal('profit_share_value', 12, 4);
            $table->decimal('profit_amount', 12, 2);
            $table->enum('status', ['pending', 'confirmed'])->default('confirmed');
            $table->timestamp('distributed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            // MySQL limits identifiers to 64 characters; Laravel's generated
            // name for this table and pair of columns is longer than that.
            $table->unique(['investment_id', 'transport_job_id'], 'ipd_investment_job_unique');
            $table->index(['investor_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investor_profit_distributions');
    }
};
