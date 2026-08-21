<?php

namespace App\Policies;

use App\Models\Tenant;
use App\Models\User;

class TenantPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('Administrator')) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['Administrator', 'Sachbearbeiter']) || $user->hasPermissionTo('manage tenants');
    }

    public function view(User $user, Tenant $tenant): bool
    {
        if ($user->hasAnyRole(['Administrator', 'Sachbearbeiter']) || $user->hasPermissionTo('manage tenants')) {
            return true;
        }

        if ($tenant->user_id && $tenant->user_id === $user->id) {
            return true; // tenant sees own record
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['Administrator', 'Sachbearbeiter']) || $user->hasPermissionTo('manage tenants');
    }

    public function update(User $user, Tenant $tenant): bool
    {
        return $this->create($user) || ($tenant->user_id && $tenant->user_id === $user->id);
    }

    public function delete(User $user, Tenant $tenant): bool
    {
        return $this->create($user);
    }
}
