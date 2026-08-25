<?php

namespace App\Policies;

use App\Models\Unit;
use App\Models\User;

class UnitPolicy
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
        return $user->hasAnyRole(['Administrator', 'Sachbearbeiter']) || $user->hasPermissionTo('manage units');
    }

    public function view(User $user, Unit $unit): bool
    {
        if ($user->hasAnyRole(['Administrator', 'Sachbearbeiter']) || $user->hasPermissionTo('manage units')) {
            return true;
        }

        // current renter can view
        if ($unit->current_renter_id && $user->id && method_exists($user, 'hasRole')) {
            // if user is linked to renter
            if ($user->hasRole('Mieter') && $unit->currentRenter && $unit->currentRenter->user_id === $user->id) {
                return true;
            }
        }

        if ($unit->property && $unit->property->owner && $unit->property->owner->user_id === $user->id) {
            return true;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['Administrator', 'Sachbearbeiter']) || $user->hasPermissionTo('manage units');
    }

    public function update(User $user, Unit $unit): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, Unit $unit): bool
    {
        return $this->create($user);
    }

    public function restore(User $user, Unit $unit): bool
    {
        return $this->create($user);
    }

    public function forceDelete(User $user, Unit $unit): bool
    {
        return $this->create($user);
    }
}
