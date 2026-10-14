<?php

namespace Tests\Feature;

use Database\Seeders\InitialSetupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(InitialSetupSeeder::class);
    }

    public function test_health_check_command_runs_successfully(): void
    {
        $this->artisan('mieterportal:health')
            ->expectsOutputToContain('MIETERPORTAL — SYSTEM HEALTH CHECK')
            ->expectsOutputToContain('1. Zentrale Datenbank-Verbindung & Mandanten')
            ->expectsOutputToContain('2. Dateispeicher & Berechtigungen')
            ->expectsOutputToContain('3. Mandanten & Kennzahlen')
            ->assertExitCode(0);
    }

    public function test_health_check_works_with_a_cache_store_without_tags(): void
    {
        // All property managements share one database; no tagged cache (Redis) is required.
        config(['cache.default' => 'database']);

        $this->artisan('mieterportal:health')->assertExitCode(0);
    }
}
