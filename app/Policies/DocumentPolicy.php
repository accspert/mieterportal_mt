<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\Owner;
use App\Models\Property;
use App\Models\Renter;
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

        return $this->userOwnsDocument($user, $document);
    }

    /**
     * Dedicated download check – applies the same ownership logic as view().
     */
    public function download(User $user, Document $document): bool
    {
        if ($user->hasAnyRole(['Administrator', 'Sachbearbeiter'])) {
            return true;
        }

        return $this->userOwnsDocument($user, $document);
    }

    /**
     * Shared ownership resolution for view/download checks.
     */
    private function userOwnsDocument(User $user, Document $document): bool
    {
        // Use User::resolvedRenter/Owner() – single authoritative 3-step fallback.
        $renter = $user->resolvedRenter();
        $owner = $user->resolvedOwner();

        $isRenterDoc = in_array($document->documentable_type, [Renter::class, Tenant::class]);

        // Document belongs directly to this renter
        if ($renter && $isRenterDoc && (int) $document->documentable_id === (int) $renter->id) {
            return true;
        }

        // Document belongs to a unit currently rented by this renter
        if ($renter && $document->documentable_type === Unit::class) {
            $unitIds = $renter->units()->pluck('id')->toArray();
            if (in_array($document->documentable_id, $unitIds)) {
                return true;
            }
        }

        // Document belongs to a property of a unit rented by this renter
        if ($renter && $document->documentable_type === Property::class) {
            $propertyIds = $renter->units()->pluck('property_id')->filter()->unique()->toArray();
            if (in_array($document->documentable_id, $propertyIds)) {
                return true;
            }
        }

        // Document belongs directly to this owner
        if ($owner && $document->documentable_type === Owner::class && (int) $document->documentable_id === (int) $owner->id) {
            return true;
        }

        // Document belongs to a property owned by this owner
        if ($owner && $document->documentable_type === Property::class) {
            $propertyIds = $owner->properties()->pluck('id')->toArray();
            if (in_array($document->documentable_id, $propertyIds)) {
                return true;
            }
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
