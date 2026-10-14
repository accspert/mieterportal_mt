<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Asks a new sign-up to confirm the e-mail address before the property management is created.
 */
class RegistrationConfirmationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $confirmationUrl,
        public string $adminName,
        public string $companyName,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Bitte bestätigen Sie Ihre Registrierung — Liegenschaftsportal')
            ->greeting("Guten Tag {$this->adminName}")
            ->line("Vielen Dank für die Registrierung von {$this->companyName} beim Liegenschaftsportal.")
            ->line('Bitte bestätigen Sie Ihre E-Mail-Adresse. Danach richten wir Ihre Verwaltung ein und Sie legen Ihr Passwort fest.')
            ->action('Registrierung bestätigen', $this->confirmationUrl)
            ->line('Der Link ist 48 Stunden gültig. Falls Sie sich nicht registriert haben, können Sie diese E-Mail ignorieren.')
            ->salutation('Freundliche Grüsse');
    }
}
