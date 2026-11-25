<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('estimates', function (Blueprint $table) {
            $table->string('estimate_type')->default('transportation');
            $table->json('details')->nullable();
            $table->text('terms')->nullable();
            $table->string('payment_terms')->nullable();
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('grand_total', 15, 2)->default(0);
            $table->string('prepared_by')->nullable();
            $table->string('checked_by')->nullable();
            $table->string('approved_by')->nullable();
            $table->string('customer_approval')->nullable();
        });

        Schema::table('estimate_items', function (Blueprint $table) {
            $table->decimal('parts_cost', 15, 2)->default(0);
            $table->decimal('labor_cost', 15, 2)->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('estimate_items', function (Blueprint $table) {
            $table->dropColumn(['parts_cost', 'labor_cost']);
        });

        Schema::table('estimates', function (Blueprint $table) {
            $table->dropColumn([
                'estimate_type',
                'details',
                'terms',
                'payment_terms',
                'tax_rate',
                'tax_amount',
                'grand_total',
                'prepared_by',
                'checked_by',
                'approved_by',
                'customer_approval',
            ]);
        });
    }
};
