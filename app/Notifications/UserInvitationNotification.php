<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Invites a newly created user to set their first password (no passwords are ever sent by mail).
 */
class UserInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $token,
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
        $expiresInDays = (int) (config('auth.passwords.invitations.expire') / 60 / 24);

        $url = route('portal.password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
            'invitation' => 1,
        ]);

        return (new MailMessage)
            ->subject("Ihr Zugang zum Liegenschaftsportal — {$this->companyName}")
            ->greeting("Guten Tag {$notifiable->name}")
            ->line("{$this->companyName} hat für Sie einen Zugang zum Liegenschaftsportal eingerichtet.")
            ->line('Bitte legen Sie über den folgenden Link Ihr persönliches Passwort fest.')
            ->action('Passwort festlegen', $url)
            ->line("Der Link ist {$expiresInDays} Tage gültig. Danach können Sie auf der Anmeldeseite über «Passwort vergessen» einen neuen Link anfordern.")
            ->salutation('Freundliche Grüsse');
    }
}
