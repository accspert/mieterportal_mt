<?php

use App\Models\CompanyTenant;
use App\Models\Property;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    if (! Schema::hasTable('company_tenants')) {
        Artisan::call('migrate', ['--path' => 'database/migrations', '--force' => true]);
    }
});

test('central domain displays tenant portal overview', function () {
    $response = $this->get('http://localhost');
    $response->assertStatus(200);
    $response->assertSee('Mieterportal Mandanten-Zugang');
});

test('tenant subdomain resolves isolated tenant database and portal', function () {
    $id = 'firma'.rand(1000, 9999);
    $domain = $id.'.localhost';

    /** @var CompanyTenant $tenant */
    $tenant = CompanyTenant::create([
        'id' => $id,
        'company_name' => 'Testverwaltung',
        'email' => "info@{$id}.local",
    ]);
    $tenant->domains()->create(['domain' => $domain]);
    $tenant->domains()->create(['domain' => $id]);

    $response = $this->get("http://{$domain}/portal/login");
    $response->assertStatus(200);
    $response->assertSee('Mieterportal');
});

test('tenant data created in one tenant is isolated from another tenant', function () {
    $idA = 'alpha'.rand(1000, 9999);
    $idB = 'beta'.rand(1000, 9999);

    /** @var CompanyTenant $tenantA */
    $tenantA = CompanyTenant::create([
        'id' => $idA,
        'company_name' => 'Verwaltung Alpha',
    ]);
    $tenantA->domains()->create(['domain' => $idA.'.localhost']);
    $tenantA->domains()->create(['domain' => $idA]);

    /** @var CompanyTenant $tenantB */
    $tenantB = CompanyTenant::create([
        'id' => $idB,
        'company_name' => 'Verwaltung Beta',
    ]);
    $tenantB->domains()->create(['domain' => $idB.'.localhost']);
    $tenantB->domains()->create(['domain' => $idB]);

    $uniqueName = 'Wohnpark Alpha '.uniqid();

    // Run inside Tenant A
    $tenantA->run(function () use ($uniqueName) {
        Property::create([
            'name' => $uniqueName,
            'address' => 'Alphastrasse 10',
            'city' => 'Zürich',
            'postal_code' => '8001',
            'country' => 'Schweiz',
        ]);
    });

    // Verify it exists in Tenant A
    $tenantA->run(function () use ($uniqueName) {
        expect(Property::where('name', $uniqueName)->exists())->toBeTrue();
    });

    // Verify it DOES NOT exist in Tenant B
    $tenantB->run(function () use ($uniqueName) {
        expect(Property::where('name', $uniqueName)->exists())->toBeFalse();
    });
});

test('tenant user can log in and view tenant portal dashboard on their subdomain', function () {
    $id = 'portal'.rand(1000, 9999);
    $domain = $id.'.localhost';

    /** @var CompanyTenant $tenant */
    $tenant = CompanyTenant::create([
        'id' => $id,
        'company_name' => 'Portal Test AG',
    ]);
    $tenant->domains()->create(['domain' => $domain]);
    $tenant->domains()->create(['domain' => $id]);

    $userId = null;
    $tenant->run(function () use (&$userId) {
        $user = User::create([
            'name' => 'Mieter Test',
            'email' => 'mieter@test.local',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);
        $userId = $user->id;
    });

    $tenant->run(function () use ($userId, $domain) {
        $user = User::find($userId);
        $response = $this->actingAs($user)->get("http://{$domain}/portal/dashboard");
        $response->assertStatus(200);
        $response->assertSee('Übersicht');
    });
});

test('tenant administrator can access filament admin dashboard on their subdomain', function () {
    $id = 'adm'.rand(1000, 9999);
    $domain = $id.'.localhost';

    /** @var CompanyTenant $tenant */
    $tenant = CompanyTenant::create([
        'id' => $id,
        'company_name' => 'Admin Test AG',
    ]);
    $tenant->domains()->create(['domain' => $domain]);
    $tenant->domains()->create(['domain' => $id]);

    $adminId = null;
    $tenant->run(function () use (&$adminId) {
        $admin = User::create([
            'name' => 'Admin Test',
            'email' => 'admin@test.local',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);
        $admin->assignRole('Administrator');
        $adminId = $admin->id;
    });

    $tenant->run(function () use ($adminId, $domain) {
        $admin = User::find($adminId);
        $response = $this->actingAs($admin)->get("http://{$domain}/admin");
        $response->assertStatus(200);
        $response->assertSee('Mieterportal');
    });
});
