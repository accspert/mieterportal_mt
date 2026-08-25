<?php

namespace App\Policies;

use App\Models\Renter;
use App\Models\User;

class RenterPolicy
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

    public function view(User $user, Renter $renter): bool
    {
        if ($user->hasAnyRole(['Administrator', 'Sachbearbeiter']) || $user->hasPermissionTo('manage tenants')) {
            return true;
        }

        if ($renter->user_id && $renter->user_id === $user->id) {
            return true; // renter sees own record
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['Administrator', 'Sachbearbeiter']) || $user->hasPermissionTo('manage tenants');
    }

    public function update(User $user, Renter $renter): bool
    {
        return $this->create($user) || ($renter->user_id && $renter->user_id === $user->id);
    }

    public function delete(User $user, Renter $renter): bool
    {
        return $this->create($user);
    }

    public function restore(User $user, Renter $renter): bool
    {
        return $this->create($user);
    }

    public function forceDelete(User $user, Renter $renter): bool
    {
        return $this->create($user);
    }
}
