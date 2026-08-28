<?php

use App\Models\CompanyCapitalDraft;
use App\Models\CompanyCapitalDraftActivity;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_capital_draft_activities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_capital_draft_id');
            $table->foreign('company_capital_draft_id', 'draft_activity_fk')->references('id')->on('company_capital_drafts')->cascadeOnDelete();
            $table->string('activity_type');
            $table->string('note')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Migrate existing drafts: create 'added' activity for each existing draft
        CompanyCapitalDraft::all()->each(function (CompanyCapitalDraft $draft) {
            CompanyCapitalDraftActivity::create([
                'company_capital_draft_id' => $draft->id,
                'activity_type' => 'added',
                'note' => $draft->note,
                'metadata' => null,
                'created_by' => $draft->created_by,
                'created_at' => $draft->created_at,
                'updated_at' => $draft->created_at,
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_capital_draft_activities');
    }
};
