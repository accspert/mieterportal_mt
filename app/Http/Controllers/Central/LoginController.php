<?php

declare(strict_types=1);

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Services\CentralLoginService;
use Filament\Auth\MultiFactor\Email\EmailAuthentication;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The single login of the platform. Users only enter e-mail and password; the app knows
 * their property management from the account.
 */
class LoginController extends Controller
{
    /**
     * Time a user has to enter the second factor.
     */
    public const CHALLENGE_TTL_MINUTES = 10;

    public function __construct(protected CentralLoginService $centralLogin) {}

    public function show(Request $request): View
    {
        return view('central.login', [
            'notice' => match (true) {
                $request->boolean('abgemeldet') => 'Sie wurden erfolgreich abgemeldet.',
                $request->boolean('passwort') => 'Ihr Passwort wurde gespeichert. Sie können sich jetzt anmelden.',
                default => null,
            },
        ]);
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = $this->centralLogin->authenticate($credentials['email'], $credentials['password']);

        if ($user === null) {
            return back()
                ->withErrors(['email' => 'Die angegebenen Anmeldedaten sind ungültig.'])
                ->onlyInput('email');
        }

        if (! $user->hasMultiFactorAuthentication()) {
            return $this->centralLogin->completeLogin($request, $user);
        }

        $request->session()->put('login_challenge', [
            'user_id' => $user->getKey(),
            'expires_at' => now()->addMinutes(self::CHALLENGE_TTL_MINUTES)->getTimestamp(),
        ]);

        if (! filled($user->getAppAuthenticationSecret())) {
            EmailAuthentication::make()->sendCode($user);
        }

        return redirect()->route('auth.challenge');
    }

    public function showForgotPassword(): View
    {
        return view('central.forgot-password');
    }

    public function sendPasswordResetLink(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        $this->centralLogin->sendPasswordResetLink($request->input('email'));

        // Same answer whether or not the address exists, so accounts cannot be enumerated.
        return back()->with('success', 'Falls ein Konto mit dieser E-Mail-Adresse existiert, haben wir Ihnen einen Link zum Zurücksetzen des Passworts gesendet.');
    }
}
