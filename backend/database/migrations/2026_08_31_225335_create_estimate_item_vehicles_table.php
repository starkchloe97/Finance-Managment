<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('estimate_item_vehicles', function (Blueprint $table) {
            $table->id();

            $table->foreignId('estimate_item_id')
                ->constrained('estimate_items')
                ->cascadeOnDelete();

            /*
             * company = existing company-owned asset
             * hired   = temporary vehicle hired specifically for this estimate
             */
            $table->string('source', 20);

            /*
             * Only populated when source = company.
             */
            $table->foreignId('asset_id')
                ->nullable()
                ->constrained('assets')
                ->nullOnDelete();

            /*
             * These fields are primarily used for hired vehicles.
             *
             * We intentionally keep a snapshot here rather than referencing
             * an Asset because hired vehicles are not company assets.
             */
            $table->string('vehicle_name')->nullable();
            $table->string('make')->nullable();
            $table->string('model')->nullable();
            $table->unsignedSmallInteger('model_year')->nullable();
            $table->string('registration_number')->nullable();
            $table->string('vin')->nullable();
            $table->string('engine_number')->nullable();
            $table->string('vehicle_type')->nullable();
            $table->string('color')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['estimate_item_id', 'source']);
            $table->index('asset_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estimate_item_vehicles');
    }
};