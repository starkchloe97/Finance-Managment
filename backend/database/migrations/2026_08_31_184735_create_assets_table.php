<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();

            $table->string('asset_code')->unique();

            $table->string('asset_type')->default('vehicle');

            $table->string('name');

            // Vehicle information
            $table->string('make')->nullable();
            $table->string('model')->nullable();
            $table->unsignedSmallInteger('model_year')->nullable();

            $table->string('registration_number')->nullable()->unique();
            $table->string('vin')->nullable()->unique();
            $table->string('engine_number')->nullable()->unique();

            $table->string('vehicle_type')->nullable();
            $table->string('color')->nullable();

            // Financial information
            $table->date('purchase_date')->nullable();
            $table->decimal('purchase_price', 15, 2)->nullable();
            $table->decimal('current_value', 15, 2)->nullable();

            $table->string('status')->default('active');

            $table->text('notes')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index('asset_type');
            $table->index('status');
            $table->index('vehicle_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};