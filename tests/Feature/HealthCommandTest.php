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
            ->expectsOutputToContain('1. Datenbank-Verbindung & Status')
            ->expectsOutputToContain('2. Dateispeicher & Berechtigungen')
            ->expectsOutputToContain('3. Rollen- & Berechtigungssystem')
            ->expectsOutputToContain('4. Operative Kennzahlen & Datenbestand')
            ->assertExitCode(0);
    }
}
