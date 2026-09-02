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
        Schema::create('estimate_items', function (Blueprint $table) {

            $table->id();

            $table->foreignId('estimate_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('title');
            $table->string('category')->nullable();

            $table->decimal('quantity', 12, 2);

            $table->decimal('cost_price', 15, 2)->default(0);
            $table->decimal('sell_price', 15, 2)->default(0);

            $table->decimal('cost_total', 15, 2)->default(0);
            $table->decimal('sell_total', 15, 2)->default(0);

            $table->decimal('profit', 15, 2)->default(0);

            $table->text('remarks')->nullable();

            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estimate_items');
    }
};
