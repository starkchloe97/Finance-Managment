<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('job_expenses', function (Blueprint $table) {

    $table->id();

    $table->foreignId('job_id')
          ->constrained('transport_jobs')
          ->cascadeOnDelete();

    $table->string('title');

    $table->string('category');

    $table->decimal('amount',15,2);

    $table->date('expense_date');

    $table->text('notes')->nullable();

    $table->timestamps();

});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_expenses');
    }
};
