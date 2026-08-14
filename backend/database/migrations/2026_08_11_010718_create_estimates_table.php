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

    // Cost and sell are captured together, so profit is known as soon as the
    // quote is built. All three are sums of the line items.
    $table->decimal('estimated_cost', 15, 2)->default(0);

    $table->decimal('estimated_sell', 15, 2)->default(0);

    $table->decimal('estimated_profit', 15, 2)->default(0);

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
