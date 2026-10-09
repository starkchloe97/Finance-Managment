<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('estimate_item_vehicles', function (Blueprint $table) {
            $table->string('supplier_name')->nullable()->after('vehicle_name');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('estimate_item_vehicle_id')
                ->nullable()
                ->after('vehicle_contract_id')
                ->unique()
                ->constrained('estimate_item_vehicles')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropUnique(['estimate_item_vehicle_id']);
            $table->dropConstrainedForeignId('estimate_item_vehicle_id');
        });

        Schema::table('estimate_item_vehicles', function (Blueprint $table) {
            $table->dropColumn('supplier_name');
        });
    }
};
