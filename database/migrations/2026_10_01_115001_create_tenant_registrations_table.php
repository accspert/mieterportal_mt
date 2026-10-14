<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Self-service sign-ups of property managements. A tenant is only created
     * once the e-mail address has been confirmed.
     */
    public function up(): void
    {
        Schema::create('tenant_registrations', function (Blueprint $table) {
            $table->id();
            $table->string('company_name');
            $table->string('admin_name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('plan');
            $table->string('slug')->nullable()->unique();
            $table->string('tenant_id')->nullable();
            $table->timestamp('terms_accepted_at');
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_registrations');
    }
};
