<?php

namespace App\Policies;

use App\Models\Activity;
use App\Models\User;

class ActivityPolicy
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
        return $user->hasRole('Administrator');
    }

    public function view(User $user, Activity $activity): bool
    {
        return $user->hasRole('Administrator');
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Activity $activity): bool
    {
        return false;
    }

    public function delete(User $user, Activity $activity): bool
    {
        return false;
    }
}
