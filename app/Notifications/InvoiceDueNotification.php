<?php

namespace App\Notifications;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvoiceDueNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Invoice $invoice) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = url('/portal/invoices');

        return (new MailMessage)
            ->subject('Neue Rechnung / Abrechnung bereitgestellt: '.$this->invoice->invoice_number)
            ->greeting('Hallo '.($notifiable->name ?? '').',')
            ->line('In Ihrem Mieterportal wurde ein neuer Rechnungsbeleg erstellt.')
            ->line('Rechnungsnummer: **'.$this->invoice->invoice_number.'**')
            ->line('Betrag: **'.number_format($this->invoice->amount, 2, ',', '.').' €**')
            ->line('Fälligkeitsdatum: **'.($this->invoice->due_date ? $this->invoice->due_date->format('d.m.Y') : '-').'**')
            ->action('Rechnungsübersicht öffnen', $url);
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Neue Rechnung: '.$this->invoice->invoice_number,
            'message' => 'Betrag: '.number_format($this->invoice->amount, 2, ',', '.').' €'.($this->invoice->due_date ? ' • Fällig am '.$this->invoice->due_date->format('d.m.Y') : ''),
            'invoice_id' => $this->invoice->id,
            'invoice_number' => $this->invoice->invoice_number,
            'amount' => $this->invoice->amount,
            'due_date' => $this->invoice->due_date?->format('Y-m-d'),
            'status' => $this->invoice->status,
            'url' => route('portal.invoices.index'),
        ];
    }
}
