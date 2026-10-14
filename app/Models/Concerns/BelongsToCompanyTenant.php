<?php

namespace App\Models\Concerns;

use App\Models\CompanyTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * All property managements (tenants) share one database. While a tenant is active
 * (tenancy()->initialized, set from the logged-in user), every query is limited to its
 * records and new records are assigned to it automatically.
 *
 * The column is called company_tenant_id because tenant_id already means "renter" in this app.
 */
trait BelongsToCompanyTenant
{
    public static function bootBelongsToCompanyTenant(): void
    {
        static::addGlobalScope('company_tenant', function (Builder $query): void {
            if (tenancy()->initialized) {
                $query->where($query->qualifyColumn('company_tenant_id'), tenant()->getTenantKey());
            }
        });
    }

    /**
     * New models belong to the active property management. Done on instantiation rather than in a
     * "creating" listener, so it also works when model events are disabled (e.g. WithoutModelEvents
     * in seeders). Models loaded from the database get their stored value afterwards.
     */
    public function initializeBelongsToCompanyTenant(): void
    {
        if (tenancy()->initialized) {
            $this->setAttribute('company_tenant_id', tenant()->getTenantKey());
        }
    }

    public function companyTenant(): BelongsTo
    {
        return $this->belongsTo(CompanyTenant::class, 'company_tenant_id');
    }
}
