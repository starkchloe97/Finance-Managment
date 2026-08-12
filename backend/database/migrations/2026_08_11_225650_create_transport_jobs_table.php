<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transport_jobs', function (Blueprint $table) {

            $table->id();

            $table->string('code')->unique();

            $table->foreignId('estimate_id')->constrained();

            $table->foreignId('customer_id')->constrained();

            $table->date('job_date');

            $table->enum('status', [
                'draft',
                'ready',
                'budgeted',
                'funded',
                'in_progress',
                'completed',
                'closed',
                'cancelled'
            ])->default('draft');

            $table->decimal('quoted_amount',15,2);

            $table->decimal('planned_cost',15,2)->default(0);

            $table->decimal('actual_cost',15,2)->default(0);

            $table->decimal('profit',15,2)->default(0);

            $table->text('remarks')->nullable();

            $table->timestamps();

            $table->softDeletes();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transport_jobs');
    }
};