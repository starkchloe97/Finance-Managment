<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_contracts', function (Blueprint $table) {
            $table->id();

            $table->string('contract_number')->unique();

            $table->date('agreement_date');

            /*
             * Vendor
             */
            $table->string('vendor_name');
            $table->text('vendor_address')->nullable();

            /*
             * Customer / User
             *
             * These are intentionally plain fields for now.
             * Later we can add customer_id / investor_id.
             */
            $table->string('customer_name');
            $table->text('customer_address')->nullable();
            $table->string('customer_tin')->nullable();

            /*
             * Agreement period
             */
            $table->date('end_date');
            $table->unsignedInteger('duration_months')->nullable();

            /*
             * Agreement nature
             */
            $table->string('service_type')->default('with_driver');
            $table->boolean('fuel_included')->default(false);
            $table->boolean('routine_maintenance_included')->default(true);

            /*
             * Vehicle
             */
            $table->unsignedInteger('total_vehicles')->default(1);
            $table->string('vehicle_make')->nullable();
            $table->string('vehicle_model')->nullable();
            $table->string('vehicle_model_year')->nullable();
            $table->string('vehicle_type')->nullable();

            /*
             * Rental
             */
            $table->decimal('monthly_rental_per_vehicle', 15, 2)->default(0);
            $table->decimal('total_monthly_rental', 15, 2)->default(0);

            /*
             * Duty
             */
            $table->unsignedInteger('duty_hours_per_day')->default(10);
            $table->unsignedInteger('duty_days_per_week')->default(6);

            $table->decimal('public_holiday_rate', 15, 2)->default(0);
            $table->decimal('overtime_rate', 15, 2)->default(0);

            /*
             * Payment
             */
            $table->string('payment_terms')->nullable();
            $table->unsignedInteger('advance_months')->default(1);

            /*
             * Insurance / loss
             */
            $table->unsignedInteger('insurance_claim_period_days')->default(45);

            /*
             * Mileage
             */
            $table->unsignedInteger('monthly_mileage_limit')->default(2500);
            $table->decimal('excess_mileage_rate', 15, 2)->default(50);

            /*
             * Refrigeration
             */
            $table->boolean('refrigeration_customer_responsibility')->default(true);

            /*
             * Termination
             */
            $table->unsignedInteger('early_termination_months')->default(3);

            /*
             * Signature information
             */
            $table->string('vendor_signatory_name')->nullable();
            $table->string('vendor_signatory_designation')->nullable();
            $table->string('vendor_signatory_cnic')->nullable();
            $table->date('vendor_signature_date')->nullable();

            $table->string('customer_signatory_name')->nullable();
            $table->string('customer_signatory_designation')->nullable();
            $table->string('customer_signatory_cnic')->nullable();
            $table->date('customer_signature_date')->nullable();

            /*
             * Witness 1
             */
            $table->string('witness_1_name')->nullable();
            $table->string('witness_1_cnic')->nullable();

            /*
             * Witness 2
             */
            $table->string('witness_2_name')->nullable();
            $table->string('witness_2_cnic')->nullable();

            $table->string('status')->default('draft');

            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_contracts');
    }
};
