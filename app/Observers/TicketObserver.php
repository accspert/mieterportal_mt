<?php

namespace App\Observers;

use App\Models\Ticket;
use App\Notifications\TicketStatusUpdatedNotification;

class TicketObserver
{
    public function updated(Ticket $ticket): void
    {
        if ($ticket->isDirty('status')) {
            $previousStatus = $ticket->getOriginal('status') ?? 'open';
            $recipient = $ticket->creator ?: ($ticket->tenant?->user);

            if ($recipient) {
                $recipient->notify(new TicketStatusUpdatedNotification($ticket, $previousStatus));
            }
        }
    }
}
