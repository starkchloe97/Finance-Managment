<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_no')->unique();
            $table->string('direction', 20)->index();
            $table->string('category', 40)->index();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('transport_job_id')->nullable()->constrained('transport_jobs')->nullOnDelete()->unique();
            $table->string('party_name');
            $table->string('party_company')->nullable();
            $table->string('party_contact')->nullable();
            $table->string('party_phone', 40)->nullable();
            $table->text('party_address')->nullable();
            $table->string('party_tax_number')->nullable();
            $table->string('company_name')->nullable();
            $table->string('company_phone', 40)->nullable();
            $table->text('company_address')->nullable();
            $table->string('company_tax_number')->nullable();
            $table->date('invoice_date')->index();
            $table->date('due_date')->nullable()->index();
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->string('tax_label')->nullable();
            $table->string('tax_number')->nullable();
            $table->decimal('total', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
