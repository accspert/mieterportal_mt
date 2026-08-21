<?php

namespace App\Policies;

use App\Models\Property;
use App\Models\User;

class PropertyPolicy
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
        return $user->hasAnyRole(['Administrator', 'Sachbearbeiter']) || $user->hasPermissionTo('manage properties');
    }

    public function view(User $user, Property $property): bool
    {
        if ($user->hasAnyRole(['Administrator', 'Sachbearbeiter']) || $user->hasPermissionTo('manage properties')) {
            return true;
        }

        if ($property->owner && $property->owner->user_id === $user->id) {
            return true; // Eigentümer sees own properties
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['Administrator', 'Sachbearbeiter']) || $user->hasPermissionTo('manage properties');
    }

    public function update(User $user, Property $property): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, Property $property): bool
    {
        return $this->create($user);
    }

    public function restore(User $user, Property $property): bool
    {
        return $this->create($user);
    }

    public function forceDelete(User $user, Property $property): bool
    {
        return $user->hasRole('Administrator');
    }
}
