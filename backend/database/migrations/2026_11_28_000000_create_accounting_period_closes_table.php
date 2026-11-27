<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_period_closes', function (Blueprint $table) {
            $table->id();
            $table->enum('period_type', ['month', 'year']);
            $table->string('period_label', 7);
            $table->date('period_start');
            $table->date('period_end');
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at');
            $table->json('summary');
            $table->timestamps();
            $table->unique(['period_type', 'period_label']);
            $table->index(['period_start', 'period_end']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_period_closes');
    }
};
