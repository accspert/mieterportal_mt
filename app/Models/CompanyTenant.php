<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

/**
 * A property management (tenant). All tenants share one database; their records carry
 * company_tenant_id (see App\Models\Concerns\BelongsToCompanyTenant).
 */
class CompanyTenant extends BaseTenant
{
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

    /**
     * Tenants are active unless explicitly deactivated (the column defaults to true).
     */
    public function isActive(): bool
    {
        return (bool) ($this->getAttribute('is_active') ?? true);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'company_tenant_id');
    }
}
