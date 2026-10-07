<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('party_ntn_no')->nullable();
            $table->string('party_str_no')->nullable();
            $table->string('company_str_no')->nullable();
            $table->string('company_ntn_no')->nullable();
            $table->string('company_stnt_no')->nullable();
            $table->text('company_bank_details')->nullable();
            $table->string('company_logo_url', 2048)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn([
                'party_ntn_no',
                'party_str_no',
                'company_str_no',
                'company_ntn_no',
                'company_stnt_no',
                'company_bank_details',
                'company_logo_url',
            ]);
        });
    }
};
