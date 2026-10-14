<?php

namespace App\Policies;

use App\Models\Message;
use App\Models\User;

class MessagePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Message $message): bool
    {
        if ($message->from_user_id === $user->id || $message->to_user_id === $user->id) {
            return true;
        }

        return $user->hasRole('Administrator') || $user->hasRole('Sachbearbeiter') || $user->hasPermissionTo('manage messages');
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Message $message): bool
    {
        return $message->from_user_id === $user->id || $user->hasRole('Administrator') || $user->hasRole('Sachbearbeiter') || $user->hasPermissionTo('manage messages');
    }

    /**
     * Only the recipient (or staff) may mark a message as read.
     */
    public function markAsRead(User $user, Message $message): bool
    {
        return $message->to_user_id === $user->id || $user->hasRole('Sachbearbeiter') || $user->hasPermissionTo('manage messages');
    }

    public function delete(User $user, Message $message): bool
    {
        return $user->hasRole('Administrator') || $user->hasRole('Sachbearbeiter') || $user->hasPermissionTo('manage messages');
    }
}
