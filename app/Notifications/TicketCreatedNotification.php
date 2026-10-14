<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public Ticket $ticket;

    public function __construct(Ticket $ticket)
    {
        $this->ticket = $ticket;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        $url = route('filament.admin.resources.tickets.edit', $this->ticket);

        return (new MailMessage)
            ->subject('New Ticket: '.$this->ticket->title)
            ->greeting('Hallo,')
            ->line('A new ticket has been created:')
            ->line($this->ticket->title)
            ->action('View Ticket', $url)
            ->line('Priority: '.$this->ticket->priority);
    }

    public function toDatabase($notifiable)
    {
        return [
            'ticket_id' => $this->ticket->id,
            'title' => $this->ticket->title,
            'priority' => $this->ticket->priority,
            'created_by' => $this->ticket->created_by,
        ];
    }
}
