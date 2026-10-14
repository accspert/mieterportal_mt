<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Lets the platform operator (super admin) work in the account of a user of a property management,
 * e.g. for support, and return to the backoffice afterwards.
 */
class ImpersonationService
{
    public const SESSION_KEY = 'impersonated_by';

    /**
     * @return string URL to continue in the account of the user
     */
    public function start(User $superAdmin, User $target): string
    {
        if (! $superAdmin->isSuperAdmin()) {
            throw new RuntimeException('Nur Superadmins können sich als andere Benutzer anmelden.');
        }

        if ($target->isSuperAdmin()) {
            throw new RuntimeException('Superadmin-Konten können nicht übernommen werden.');
        }

        Log::info('Superadmin impersonation started', ['super_admin' => $superAdmin->id, 'user' => $target->id, 'tenant' => $target->company_tenant_id]);

        Auth::login($target);
        session()->regenerate();
        session()->put(self::SESSION_KEY, $superAdmin->id);

        return $target->hasAnyRole(['Administrator', 'Sachbearbeiter'])
            ? url(config('filament.path', '/admin'))
            : route('portal.dashboard');
    }

    /**
     * @return string URL of the backoffice, or of the login when there is nothing to return to
     */
    public function stop(): string
    {
        $superAdminId = session()->pull(self::SESSION_KEY);
        // The super admin belongs to no property management: bypass the tenant scope of the impersonated user.
        $superAdmin = $superAdminId ? User::withoutGlobalScopes()->find($superAdminId) : null;

        if ($superAdmin === null || ! $superAdmin->isSuperAdmin()) {
            Auth::logout();

            return CentralLoginService::loginUrl();
        }

        Log::info('Superadmin impersonation ended', ['super_admin' => $superAdmin->id]);

        if (tenancy()->initialized) {
            tenancy()->end();
        }

        Auth::login($superAdmin);
        session()->regenerate();

        return url('/superadmin');
    }

    public static function isImpersonating(): bool
    {
        return session()->has(self::SESSION_KEY);
    }
}
