<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_daily_reports', function (Blueprint $table) {
            $table->id();

            $table->foreignId('contract_vehicle_id')
                ->constrained('contract_vehicles')
                ->cascadeOnDelete();

            $table->date('report_date');

            $table->time('time_in')->nullable();
            $table->time('time_out')->nullable();

            $table->decimal('meter_in', 12, 2)->nullable();
            $table->decimal('meter_out', 12, 2)->nullable();

            $table->decimal('fuel_drawn', 10, 2)->nullable();

            /*
             * Calculated values
             */
            $table->unsignedInteger('total_minutes')->default(0);
            $table->unsignedInteger('normal_minutes')->default(0);
            $table->unsignedInteger('overtime_minutes')->default(0);

            $table->decimal('total_running', 12, 2)->default(0);

            $table->decimal('overtime_amount', 15, 2)->default(0);

            $table->decimal('excess_mileage', 12, 2)->default(0);
            $table->decimal('excess_mileage_amount', 15, 2)->default(0);

            /*
             * Daily billing classification
             */
            $table->boolean('is_public_holiday')->default(false);
            $table->boolean('is_weekly_off')->default(false);

            $table->enum('status', [
                'draft',
                'approved',
                'rejected',
            ])->default('draft');

            $table->text('remarks')->nullable();

            $table->timestamps();

            /*
             * One daily report per vehicle per date.
             */
            $table->unique([
                'contract_vehicle_id',
                'report_date',
            ]);

            $table->index([
                'contract_vehicle_id',
                'report_date',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_daily_reports');
    }
};