<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            // Property management (tenant) this record belongs to – all tenants share one database.
            $table->string('company_tenant_id');
            $table->foreign('company_tenant_id')->references('id')->on('company_tenants')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('property_id')->constrained('properties')->cascadeOnDelete();
            $table->string('number')->nullable();
            $table->string('floor')->nullable();
            $table->decimal('area', 8, 2)->nullable();
            $table->unsignedTinyInteger('rooms')->nullable();
            $table->enum('status', ['vacant', 'occupied', 'maintenance', 'inactive'])->default('vacant');
            $table->foreignId('current_renter_id')->nullable()->constrained('renters')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
