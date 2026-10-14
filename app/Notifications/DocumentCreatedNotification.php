<?php

namespace App\Notifications;

use App\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DocumentCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Document $document) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $categoryLabel = match ($this->document->category) {
            'contract' => 'Mietvertrag / Vertrag',
            'invoice' => 'Abrechnung / Rechnung',
            'utility_bill' => 'Nebenkostenabrechnung',
            'protocol' => 'Übergabeprotokoll',
            default => 'Allgemeines Dokument',
        };

        $url = url('/portal/documents');

        return (new MailMessage)
            ->subject('Neues Dokument bereitgestellt: '.$this->document->filename)
            ->greeting('Hallo '.($notifiable->name ?? '').',')
            ->line('In Ihrer Dokumentenablage im Mieterportal wurde ein neues Dokument bereitgestellt.')
            ->line('Dokument: **'.$this->document->filename.'**')
            ->line('Kategorie: **'.$categoryLabel.'**')
            ->action('Dokumentenablage öffnen', $url);
    }

    public function toDatabase(object $notifiable): array
    {
        $categoryLabel = match ($this->document->category) {
            'contract' => 'Vertrag',
            'invoice' => 'Rechnung',
            'utility_bill' => 'Nebenkosten',
            'protocol' => 'Protokoll',
            default => 'Allgemein',
        };

        return [
            'title' => 'Neues Dokument: '.$this->document->filename,
            'message' => 'Kategorie: '.$categoryLabel.' steht in Ihrer Dokumentenablage bereit.',
            'document_id' => $this->document->id,
            'filename' => $this->document->filename,
            'category' => $this->document->category,
            'url' => route('portal.documents.index'),
        ];
    }
}
