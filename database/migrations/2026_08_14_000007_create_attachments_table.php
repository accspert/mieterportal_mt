<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            // Property management (tenant) this record belongs to – all tenants share one database.
            $table->string('company_tenant_id');
            $table->foreign('company_tenant_id')->references('id')->on('company_tenants')->cascadeOnUpdate()->cascadeOnDelete();
            $table->morphs('attachable');
            $table->string('filename');
            $table->string('path');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};
