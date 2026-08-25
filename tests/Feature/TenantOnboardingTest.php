<?php

namespace Tests\Feature;

use App\Models\CompanyTenant;
use App\Services\TenantOnboardingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantOnboardingTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CompanyTenant::all()->each(function (CompanyTenant $tenant) {
            $tenant->delete(); // Triggers Jobs\DeleteDatabase
        });

        // Also clean up any leftover sqlite files in database/
        foreach (glob(database_path('tenant_*.sqlite')) ?: [] as $file) {
            @unlink($file);
        }
        foreach (glob(database_path('tenant_*')) ?: [] as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }

        parent::tearDown();
    }

    public function test_it_can_create_a_tenant_via_service(): void
    {
        $service = app(TenantOnboardingService::class);

        $tenant = $service->createTenant([
            'company_name' => 'Alpen Real Estate AG',
            'subdomain' => 'alpen-immo-'.uniqid(),
            'admin_name' => 'Max Muster',
            'admin_email' => 'admin@alpen-immo.ch',
            'admin_password' => 'secret-password-123',
            'phone' => '+41 44 123 45 67',
            'plan' => 'standard',
        ]);

        $this->assertInstanceOf(CompanyTenant::class, $tenant);
        $this->assertEquals('Alpen Real Estate AG', $tenant->company_name);

        $this->assertDatabaseHas('company_tenants', [
            'id' => $tenant->id,
            'company_name' => 'Alpen Real Estate AG',
        ]);

        $this->assertDatabaseHas('domains', [
            'tenant_id' => $tenant->id,
        ]);
    }

    public function test_it_can_onboard_a_tenant_via_api(): void
    {
        $subdomain = 'bern-immo-'.uniqid();

        $response = $this->postJson(route('tenants.onboard'), [
            'company_name' => 'Immobilien Zentrum Bern',
            'subdomain' => $subdomain,
            'admin_name' => 'Hans Meier',
            'admin_email' => 'hans@bern-immo.ch',
            'admin_password' => 'secure-pass-2026',
            'phone' => '+41 31 987 65 43',
            'plan' => 'enterprise',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('tenant.id', $subdomain)
            ->assertJsonPath('tenant.company_name', 'Immobilien Zentrum Bern');

        $this->assertDatabaseHas('company_tenants', [
            'id' => $subdomain,
        ]);
    }
}
