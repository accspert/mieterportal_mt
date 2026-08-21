<?php

namespace App\Listeners;

use App\Events\TicketCreated;
use App\Jobs\SendTicketNotificationJob;

class SendTicketNotifications
{
    public function handle(TicketCreated $event): void
    {
        // Dispatch the actual notification job in the background.
        SendTicketNotificationJob::dispatch($event->ticket);
    }
}
