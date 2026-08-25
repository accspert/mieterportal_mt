<?php

namespace App\Policies;

use App\Models\Owner;
use App\Models\User;

class OwnerPolicy
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
        return $user->hasAnyRole(['Administrator', 'Sachbearbeiter']) || $user->hasPermissionTo('manage owners');
    }

    public function view(User $user, Owner $owner): bool
    {
        if ($user->hasAnyRole(['Administrator', 'Sachbearbeiter']) || $user->hasPermissionTo('manage owners')) {
            return true;
        }

        if ($owner->user_id && $owner->user_id === $user->id) {
            return true; // owner sees own record
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['Administrator', 'Sachbearbeiter']) || $user->hasPermissionTo('manage owners');
    }

    public function update(User $user, Owner $owner): bool
    {
        return $this->create($user) || ($owner->user_id && $owner->user_id === $user->id);
    }

    public function delete(User $user, Owner $owner): bool
    {
        return $this->create($user);
    }

    public function restore(User $user, Owner $owner): bool
    {
        return $this->create($user);
    }

    public function forceDelete(User $user, Owner $owner): bool
    {
        return $this->create($user);
    }
}
