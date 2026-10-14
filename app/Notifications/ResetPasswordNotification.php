<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Password reset link for the tenant portal (the Filament panel sends its own notification).
 */
class ResetPasswordNotification extends ResetPassword implements ShouldQueue
{
    use Queueable;

    public function toMail($notifiable): MailMessage
    {
        $expiresInMinutes = config('auth.passwords.users.expire');

        return (new MailMessage)
            ->subject('Passwort zurücksetzen — Liegenschaftsportal')
            ->greeting('Guten Tag')
            ->line('Sie erhalten diese E-Mail, weil für Ihr Konto das Zurücksetzen des Passworts angefordert wurde.')
            ->action('Passwort zurücksetzen', $this->resetUrl($notifiable))
            ->line("Der Link ist {$expiresInMinutes} Minuten gültig.")
            ->line('Falls Sie das nicht angefordert haben, ist keine weitere Aktion erforderlich.')
            ->salutation('Freundliche Grüsse');
    }

    protected function resetUrl($notifiable): string
    {
        return route('portal.password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);
    }
}
