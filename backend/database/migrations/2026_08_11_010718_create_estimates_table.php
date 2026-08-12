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
        Schema::create('estimates', function (Blueprint $table) {

    $table->id();

    $table->string('code')->unique();

    $table->foreignId('customer_id')->constrained();

    $table->date('estimate_date');

    $table->date('valid_until')->nullable();

    $table->string('pickup');

    $table->string('destination');

    $table->enum('service_type', [
        'goods',
        'vehicle'
    ]);

    $table->decimal('subtotal', 15, 2)->default(0);

    $table->decimal('margin', 15, 2)->default(0);

    $table->enum('margin_type', [
        'fixed',
        'percentage'
    ])->default('fixed');

    $table->decimal('total', 15, 2)->default(0);

    $table->enum('status', [
        'draft',
        'sent',
        'accepted',
        'rejected',
        'expired'
    ])->default('draft');

    $table->text('remarks')->nullable();

    $table->timestamps();

    $table->softDeletes();

});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estimates');
    }
};
