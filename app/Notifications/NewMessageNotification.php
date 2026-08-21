<?php

namespace App\Notifications;

use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class NewMessageNotification extends Notification
{
    use Queueable;

    public function __construct(public Message $messageModel) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $senderName = $this->messageModel->sender?->name ?? 'Verwaltung';
        $url = url('/portal/messages');

        return (new MailMessage)
            ->subject('Neue Nachricht: '.($this->messageModel->subject ?: 'Kundenportal Benachrichtigung'))
            ->greeting('Hallo '.($notifiable->name ?? '').',')
            ->line('Sie haben eine neue Nachricht von **'.$senderName.'** erhalten:')
            ->line('"'.Str::limit($this->messageModel->body, 200).'"')
            ->action('Nachricht öffnen', $url);
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'message_id' => $this->messageModel->id,
            'thread_id' => $this->messageModel->thread_id,
            'from_user_id' => $this->messageModel->from_user_id,
            'from_user_name' => $this->messageModel->sender?->name ?? 'Verwaltung',
            'subject' => $this->messageModel->subject,
            'preview' => Str::limit($this->messageModel->body, 100),
        ];
    }
}
