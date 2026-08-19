<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('investments', function (Blueprint $table) {
            $table->timestamp('matured_at')->nullable()->change();
            $table->timestamp('withdrawn_at')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('investments', function (Blueprint $table) {
            $table->date('matured_at')->nullable()->change();
            $table->date('withdrawn_at')->nullable()->change();
        });
    }
};
