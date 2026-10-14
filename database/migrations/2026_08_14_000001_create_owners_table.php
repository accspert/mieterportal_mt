<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('owners', function (Blueprint $table) {
            $table->id();
            // Property management (tenant) this record belongs to – all tenants share one database.
            $table->string('company_tenant_id');
            $table->foreign('company_tenant_id')->references('id')->on('company_tenants')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('company_name')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('street')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->default('DE');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('owners');
    }
};
