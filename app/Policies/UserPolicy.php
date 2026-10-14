<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        // Platform operators manage all accounts in /superadmin; administrators only see their own
        // property management (users are scoped by company_tenant_id).
        if ($user->isSuperAdmin() || $user->hasRole('Administrator')) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasRole('Administrator') || $user->hasPermissionTo('manage users');
    }

    public function view(User $user, User $model): bool
    {
        return $user->hasRole('Administrator') || $user->id === $model->id || $user->hasPermissionTo('manage users');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('Administrator') || $user->hasPermissionTo('manage users');
    }

    public function update(User $user, User $model): bool
    {
        return $user->hasRole('Administrator') || $user->id === $model->id || $user->hasPermissionTo('manage users');
    }

    public function delete(User $user, User $model): bool
    {
        return $user->hasRole('Administrator') && $user->id !== $model->id;
    }
}
