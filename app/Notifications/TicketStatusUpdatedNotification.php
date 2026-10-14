<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketStatusUpdatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Ticket $ticket, public string $previousStatus) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $statusText = match ($this->ticket->status) {
            'in_progress' => 'In Bearbeitung',
            'waiting' => 'Wartet auf Rückmeldung',
            'done' => 'Erledigt',
            'resolved' => 'Gelöst',
            'closed' => 'Geschlossen',
            default => $this->ticket->status,
        };

        $url = url('/portal/tickets/'.$this->ticket->id);

        return (new MailMessage)
            ->subject('Status-Update zu Ticket #'.$this->ticket->id.': '.$this->ticket->title)
            ->greeting('Hallo '.($notifiable->name ?? '').',')
            ->line('Der Status Ihres Anliegens hat sich geändert auf: **'.$statusText.'**')
            ->line('Ticket: '.$this->ticket->title)
            ->action('Ticket im Mieterportal ansehen', $url)
            ->line('Vielen Dank für Ihre Geduld.');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'ticket_id' => $this->ticket->id,
            'title' => $this->ticket->title,
            'status' => $this->ticket->status,
            'previous_status' => $this->previousStatus,
            'message' => 'Status geändert auf '.$this->ticket->status,
        ];
    }
}
