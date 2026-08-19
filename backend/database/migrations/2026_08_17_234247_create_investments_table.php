<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investments', function (Blueprint $table) {
            $table->id();

            $table->string('investment_code')->unique();

            $table->foreignId('investor_id')
                ->constrained('investors')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->date('investment_date');

            $table->decimal('amount', 15, 2);

            $table->unsignedInteger('period_months')->nullable();

            $table->unsignedInteger('return_policy_days')->nullable();

            $table->decimal('min_return_percent', 5, 2)->nullable();

            $table->decimal('max_return_percent', 5, 2)->nullable();

            $table->enum('status', [
                'active',
                'matured',
                'withdrawn',
                'settled',
                'cancelled',
            ])->default('active');

            $table->date('matured_at')->nullable();

            $table->date('withdrawn_at')->nullable();

            $table->decimal('deduction_amount', 15, 2)
                ->default(0);

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->softDeletes();

            $table->index([
                'investor_id',
                'status',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investments');
    }
};