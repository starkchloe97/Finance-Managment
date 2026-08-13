<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_budget_items', function (Blueprint $table) {
            // drop existing foreign if present
            $table->dropForeign(['job_id']);
            $table->foreign('job_id')
                  ->references('id')
                  ->on('transport_jobs')
                  ->cascadeOnDelete();
        });

        Schema::table('job_expenses', function (Blueprint $table) {
            $table->dropForeign(['job_id']);
            $table->foreign('job_id')
                  ->references('id')
                  ->on('transport_jobs')
                  ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('job_budget_items', function (Blueprint $table) {
            $table->dropForeign(['job_id']);
            $table->foreign('job_id')
                  ->references('id')
                  ->on('jobs')
                  ->cascadeOnDelete();
        });

        Schema::table('job_expenses', function (Blueprint $table) {
            $table->dropForeign(['job_id']);
            $table->foreign('job_id')
                  ->references('id')
                  ->on('jobs')
                  ->cascadeOnDelete();
        });
    }
};
