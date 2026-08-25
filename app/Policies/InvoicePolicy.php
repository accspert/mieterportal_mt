<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
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
        return $user->hasAnyRole(['Administrator', 'Sachbearbeiter']) || $user->hasPermissionTo('manage invoices');
    }

    public function view(User $user, Invoice $invoice): bool
    {
        if ($invoice->renter && $invoice->renter->user_id === $user->id) {
            return true;
        }

        if ($invoice->owner && $invoice->owner->user_id === $user->id) {
            return true;
        }

        return $user->hasAnyRole(['Administrator', 'Sachbearbeiter']) || $user->hasPermissionTo('manage invoices');
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['Administrator', 'Sachbearbeiter']) || $user->hasPermissionTo('manage invoices');
    }

    public function update(User $user, Invoice $invoice): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, Invoice $invoice): bool
    {
        return $this->create($user);
    }

    public function restore(User $user, Invoice $invoice): bool
    {
        return $this->create($user);
    }

    public function forceDelete(User $user, Invoice $invoice): bool
    {
        return $this->create($user);
    }
}
