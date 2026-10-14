<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The platform serves Swiss property managements: new owners and properties default to Switzerland.
     */
    public function up(): void
    {
        foreach (['owners', 'properties'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->string('country')->default('CH')->change();
            });
        }
    }

    public function down(): void
    {
        foreach (['owners', 'properties'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->string('country')->default('DE')->change();
            });
        }
    }
};
