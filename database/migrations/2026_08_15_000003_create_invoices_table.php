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
            // Property management (tenant) this record belongs to – all tenants share one database.
            $table->string('company_tenant_id');
            $table->foreign('company_tenant_id')->references('id')->on('company_tenants')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('invoice_number');
            $table->foreignId('renter_id')->nullable()->constrained('renters')->nullOnDelete();
            $table->foreignId('owner_id')->nullable()->constrained('owners')->nullOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->decimal('amount', 10, 2);
            $table->date('due_date');
            $table->enum('status', ['draft', 'sent', 'paid', 'overdue', 'cancelled'])->default('draft');
            $table->string('pdf_path')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Invoice numbers are unique per property management, not across the platform.
            $table->unique(['company_tenant_id', 'invoice_number']);
            $table->softDeletes();
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->index(['status', 'due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
