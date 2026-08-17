<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * An append-only record of what happened to a job and who did it. Nothing here
 * is ever updated or deleted — a row is the fact that something occurred, so
 * correcting it would defeat the point.
 *
 * The FK is spelled out against 'transport_jobs' because Laravel would infer
 * the queue's own 'jobs' table from a bare job_id column.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transport_job_activities', function (Blueprint $table) {

            $table->id();

            $table->foreignId('job_id')->constrained('transport_jobs')->cascadeOnDelete();

            $table->string('event_type');

            $table->string('description');

            // The before and after of whatever changed, for the events where
            // that is meaningful. Both sides are absent on a creation event.
            $table->json('old_value')->nullable();

            $table->json('new_value')->nullable();

            // Nullable because a job can be touched outside a request — a
            // seeder or a console command has no signed-in user to credit.
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            // Read one job at a time, newest first, and never updated — so this
            // is the only index the table needs.
            $table->timestamp('created_at')->nullable();

            $table->index(['job_id', 'created_at']);

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transport_job_activities');
    }
};
