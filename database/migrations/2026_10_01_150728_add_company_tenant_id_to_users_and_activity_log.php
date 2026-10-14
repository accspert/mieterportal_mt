<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Users and audit entries belong to a property management as well. Both columns are nullable:
     * platform accounts and system events are not tied to a property management.
     */
    public function up(): void
    {
        foreach (['users', 'activity_log'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->string('company_tenant_id')->nullable()->after('id');
                $table->foreign('company_tenant_id')->references('id')->on('company_tenants')->cascadeOnUpdate()->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (['users', 'activity_log'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropForeign(['company_tenant_id']);
                $table->dropColumn('company_tenant_id');
            });
        }
    }
};
