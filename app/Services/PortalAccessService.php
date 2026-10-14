<?php

namespace App\Services;

use App\Models\Owner;
use App\Models\Renter;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Portal access for renters, owners and staff of a property management.
 *
 * Accounts are only ever created by invitation: the person receives a link and sets the password
 * personally – nobody at the property management sees or hands out passwords. Existing accounts are
 * never taken over or overwritten.
 */
class PortalAccessService
{
    /**
     * Creates the portal account of a renter or owner, links it to the record and sends the invitation.
     *
     * @throws RuntimeException with a user-facing message when no account can be created
     */
    public function invite(Renter|Owner $person, ?string $email = null, ?string $name = null): User
    {
        if ($person->user_id !== null) {
            throw new RuntimeException('Für diese Person besteht bereits ein Portal-Zugang.');
        }

        $user = DB::transaction(function () use ($person, $email, $name): User {
            $user = $this->createAccount(
                $name ?: $this->displayName($person),
                $email ?: (string) $person->email,
                $person instanceof Owner ? 'Eigentümer' : 'Mieter',
            );

            $person->update(['user_id' => $user->id, 'email' => $user->email]);

            return $user;
        });

        $user->sendInvitation((string) tenant()?->company_name);

        return $user;
    }

    /**
     * Creates a staff account (administrator or clerk) and sends the invitation.
     *
     * @param  list<string>  $roles
     */
    public function inviteStaff(string $name, string $email, array $roles): User
    {
        $user = $this->createAccount($name, $email, $roles);
        $user->sendInvitation((string) tenant()?->company_name);

        return $user;
    }

    /**
     * Creates a platform operator account (belongs to no property management) and sends the invitation.
     */
    public function inviteSuperAdmin(string $name, string $email): User
    {
        $user = $this->createAccount($name, $email, []);
        $user->forceFill(['is_super_admin' => true, 'company_tenant_id' => null])->save();
        $user->sendInvitation('Liegenschaftsportal');

        return $user;
    }

    /**
     * Sends a link to set a new password (nobody at the property management sees passwords).
     */
    public function sendPasswordLink(User $user): void
    {
        Password::sendResetLink(['email' => $user->email]);
    }

    /**
     * @param  string|list<string>  $roles
     */
    protected function createAccount(string $name, string $email, string|array $roles): User
    {
        $email = mb_strtolower(trim($email));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Bitte hinterlegen Sie zuerst eine gültige E-Mail-Adresse.');
        }

        // One account per e-mail address across the platform. Deliberately checked without the
        // tenant scope: an address used at another property management must not be overwritten either.
        if (User::withoutGlobalScopes()->whereRaw('LOWER(email) = ?', [$email])->exists()) {
            throw new RuntimeException("Mit der E-Mail-Adresse {$email} besteht bereits ein Konto. Bitte verwenden Sie eine andere Adresse.");
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            // The person sets the password via the invitation link; this one is never used.
            'password' => Str::password(40),
        ]);

        $user->syncRoles($roles);

        return $user;
    }

    protected function displayName(Renter|Owner $person): string
    {
        $fullName = trim("{$person->first_name} {$person->last_name}");

        return $fullName !== '' ? $fullName : (string) ($person->company_name ?? $person->email);
    }
}
