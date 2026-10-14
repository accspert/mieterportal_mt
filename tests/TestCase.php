<?php

namespace Tests;

use App\Models\CompanyTenant;
use App\Policies\PropertyPolicy;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Schema;

/**
 * Base test case for Pest/PHPUnit tests.
 *
 * Every test runs inside a property management ("test"), like every real request after the login:
 * records created in a test belong to it and all queries are limited to it.
 *
 * Declaring dynamic properties here helps static analyzers (Intelephense/Psalm)
 * recognize properties assigned in test closures (e.g., via `beforeEach`).
 *
 * @property PropertyPolicy $policy
 */
abstract class TestCase extends BaseTestCase
{
    protected ?PropertyPolicy $policy = null;

    protected CompanyTenant $companyTenant;

    protected function setUp(): void
    {
        parent::setUp();

        // Tests without a database (no RefreshDatabase) run without a property management.
        if (! Schema::hasTable('company_tenants')) {
            return;
        }

        $this->companyTenant = CompanyTenant::query()->firstOrCreate(
            ['id' => 'test'],
            ['company_name' => 'Testverwaltung AG', 'plan' => 'standard', 'is_active' => true],
        );

        tenancy()->initialize($this->companyTenant);
    }

    protected function tearDown(): void
    {
        if (tenancy()->initialized) {
            tenancy()->end();
        }

        parent::tearDown();
    }
}
