<?php

namespace App\Rules;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\Unique;

/**
 * Validation rules limited to the active property management. All property managements share
 * one database, so a plain "exists:units,id" would also accept records of other tenants.
 */
class TenantScoped
{
    public static function exists(string $table, string $column = 'id'): Exists
    {
        return Rule::exists($table, $column)->where('company_tenant_id', tenant()?->getTenantKey());
    }

    public static function unique(string $table, string $column): Unique
    {
        return Rule::unique($table, $column)->where('company_tenant_id', tenant()?->getTenantKey());
    }
}
