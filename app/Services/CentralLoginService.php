<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

/**
 * The single login of the platform. Every account belongs to exactly one property management
 * (users.company_tenant_id); after the login the app activates it from the user.
 */
class CentralLoginService
{
    /**
     * Absolute URL of the platform.
     */
    public static function centralUrl(string $path = ''): string
    {
        return rtrim((string) config('app.url'), '/').$path;
    }

    /**
     * @param  array<string, mixed>  $query
     */
    public static function loginUrl(array $query = []): string
    {
        return static::centralUrl(route('central.login', $query, false));
    }

    /**
     * The user with these credentials whose property management is active, or null.
     */
    public function authenticate(string $email, string $password): ?User
    {
        $user = User::query()
            ->with('companyTenant')
            ->whereRaw('LOWER(email) = ?', [mb_strtolower(trim($email))])
            ->first();

        if ($user === null) {
            // Spend the same time as a real check, so unknown addresses cannot be told apart.
            Hash::check($password, '$2y$12$C9X3nY2qJ0q8h5mJ9QpQKuQ8i7n9Kp8cJ1H0dJb3H4f0dQm8u0YzS');

            return null;
        }

        $isTenantActive = $user->company_tenant_id === null || $user->companyTenant?->isActive();

        return Hash::check($password, $user->password) && $isTenantActive ? $user : null;
    }

    /**
     * Logs the user in and opens the admin panel or the portal.
     */
    public function completeLogin(Request $request, User $user): RedirectResponse
    {
        $request->session()->forget('login_challenge');

        Auth::login($user);
        $request->session()->regenerate();

        if ($user->isSuperAdmin()) {
            return redirect('/superadmin');
        }

        if ($user->hasAnyRole(['Administrator', 'Sachbearbeiter'])) {
            return redirect(config('filament.path', '/admin'));
        }

        return redirect()->route('portal.dashboard');
    }

    public function sendPasswordResetLink(string $email): void
    {
        Password::sendResetLink(['email' => mb_strtolower(trim($email))]);
    }
}
