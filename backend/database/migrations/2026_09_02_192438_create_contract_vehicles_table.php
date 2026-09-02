<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_vehicles', function (Blueprint $table) {
            $table->id();

            $table->foreignId('vehicle_contract_id')
                ->constrained('vehicle_contracts')
                ->cascadeOnDelete();

            $table->string('vehicle_number')->nullable();

            $table->string('make');
            $table->string('model');
            $table->string('model_year')->nullable();
            $table->string('vehicle_type')->nullable();

            $table->decimal('monthly_rental', 15, 2)->default(0);

            $table->unsignedInteger('duty_hours_per_day')->default(10);
            $table->unsignedInteger('duty_days_per_week')->default(6);

            $table->decimal('public_holiday_rate', 15, 2)->default(0);
            $table->decimal('overtime_rate', 15, 2)->default(0);

            $table->unsignedInteger('monthly_mileage_limit')->default(0);
            $table->decimal('excess_mileage_rate', 15, 2)->default(0);

            $table->enum('status', [
                'active',
                'inactive',
                'completed',
            ])->default('active');

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index('vehicle_number');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_vehicles');
    }
};
