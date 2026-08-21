<?php

namespace App\Jobs;

use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketCreatedNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;

class SendTicketNotificationJob implements ShouldQueue
{
    use Dispatchable, Queueable, SerializesModels;

    public Ticket $ticket;

    public function __construct(Ticket $ticket)
    {
        $this->ticket = $ticket;
    }

    public function handle(): void
    {
        // Notify assignee if present, otherwise notify all administrators
        $recipients = [];

        if ($this->ticket->assigned_to) {
            $user = User::find($this->ticket->assigned_to);
            if ($user) {
                $recipients[] = $user;
            }
        }

        if (empty($recipients)) {
            $recipients = User::role('Administrator')->get()->all();
        }

        if (! empty($recipients)) {
            Notification::send($recipients, new TicketCreatedNotification($this->ticket));
        }
    }
}
