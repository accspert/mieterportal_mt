<?php

declare(strict_types=1);

namespace App\Models;

use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

class CompanyTenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains;

    protected $table = 'company_tenants';

    /**
     * Define custom columns that are stored as explicit columns on the central tenants table.
     */
    public static function getCustomColumns(): array
    {
        return [
            'id',
            'company_name',
            'email',
            'phone',
            'plan',
            'is_active',
        ];
    }
}
