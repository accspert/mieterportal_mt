<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('Sachbearbeiter') || $user->hasPermissionTo('manage tickets');
    }

    public function view(User $user, Ticket $ticket): bool
    {
        // creator, assignee, tenant owner or staff can view
        if ($ticket->created_by === $user->id || $ticket->assigned_to === $user->id) {
            return true;
        }

        if ($ticket->tenant && $ticket->tenant->user_id === $user->id) {
            return true;
        }

        return $user->hasRole('Sachbearbeiter') || $user->hasPermissionTo('manage tickets');
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['Mieter', 'Sachbearbeiter']) || $user->hasPermissionTo('manage tickets');
    }

    public function update(User $user, Ticket $ticket): bool
    {
        return $ticket->created_by === $user->id || $ticket->assigned_to === $user->id || $user->hasRole('Sachbearbeiter');
    }

    public function close(User $user, Ticket $ticket): bool
    {
        return $this->update($user, $ticket);
    }

    public function delete(User $user, Ticket $ticket): bool
    {
        return $user->hasRole('Sachbearbeiter') || $user->hasPermissionTo('manage tickets');
    }
}
