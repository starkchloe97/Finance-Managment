<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Working notes for the people running the job — what the driver said, why it
 * was delayed. `remarks` is the line that came off the estimate and describes
 * the deal; this is separate and is never quoted back to the customer.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transport_jobs', function (Blueprint $table) {
            $table->text('internal_notes')->nullable()->after('remarks');
        });
    }

    public function down(): void
    {
        Schema::table('transport_jobs', function (Blueprint $table) {
            $table->dropColumn('internal_notes');
        });
    }
};
