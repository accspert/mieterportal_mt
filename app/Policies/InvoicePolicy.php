<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('Sachbearbeiter') || $user->hasPermissionTo('manage invoices');
    }

    public function view(User $user, Invoice $invoice): bool
    {
        if ($invoice->tenant && $invoice->tenant->user_id === $user->id) {
            return true;
        }

        if ($invoice->owner && $invoice->owner->user_id === $user->id) {
            return true;
        }

        return $user->hasRole('Sachbearbeiter') || $user->hasPermissionTo('manage invoices');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('Sachbearbeiter') || $user->hasPermissionTo('manage invoices');
    }

    public function update(User $user, Invoice $invoice): bool
    {
        return $user->hasRole('Sachbearbeiter') || $user->hasPermissionTo('manage invoices');
    }

    public function delete(User $user, Invoice $invoice): bool
    {
        return $user->hasRole('Sachbearbeiter') || $user->hasPermissionTo('manage invoices');
    }
}
