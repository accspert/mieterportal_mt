<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\Property;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;

class DocumentPolicy
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
        return $user->hasAnyRole(['Administrator', 'Sachbearbeiter']) || $user->hasPermissionTo('manage documents');
    }

    public function view(User $user, Document $document): bool
    {
        if ($user->hasAnyRole(['Administrator', 'Sachbearbeiter']) || $user->hasPermissionTo('manage documents')) {
            return true;
        }

        // Owner, tenant or staff with permission can view
        $doc = $document->documentable;

        if ($doc instanceof Property && $doc->owner && $doc->owner->user_id === $user->id) {
            return true;
        }

        if ($doc instanceof Unit && $doc->property && $doc->property->owner && $doc->property->owner->user_id === $user->id) {
            return true;
        }

        if ($doc instanceof Tenant && $doc->user_id === $user->id) {
            return true;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['Administrator', 'Sachbearbeiter']) || $user->hasPermissionTo('manage documents');
    }

    public function update(User $user, Document $document): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, Document $document): bool
    {
        return $this->create($user);
    }

    public function restore(User $user, Document $document): bool
    {
        return $this->create($user);
    }

    public function forceDelete(User $user, Document $document): bool
    {
        return $this->create($user);
    }
}
