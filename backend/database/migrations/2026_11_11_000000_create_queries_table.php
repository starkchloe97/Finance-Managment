<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('queries', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('phone', 32);
            $table->string('service', 160)->nullable();
            $table->string('pickup_location', 160)->nullable();
            $table->string('dropoff_location', 160)->nullable();
            $table->text('message')->nullable();
            $table->string('status', 24)->default('new')->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('queries');
    }
};
