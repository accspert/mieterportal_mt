<?php

declare(strict_types=1);

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\CentralLoginService;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Auth\MultiFactor\Email\EmailAuthentication;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Second step of the login for accounts with a second factor (authenticator app or e-mail code).
 */
class TwoFactorChallengeController extends Controller
{
    public function __construct(protected CentralLoginService $centralLogin) {}

    public function show(Request $request): View|RedirectResponse
    {
        $user = $this->challengedUser($request);

        if ($user === null) {
            return redirect()->route('central.login');
        }

        return view('central.challenge', [
            'usesAuthenticatorApp' => filled($user->getAppAuthenticationSecret()),
        ]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $user = $this->challengedUser($request);

        if ($user === null) {
            return redirect()->route('central.login');
        }

        $code = preg_replace('/\s+/', '', (string) $request->validate(['code' => ['required', 'string', 'max:64']])['code']);

        if (! $this->isValidCode($user, $code)) {
            return back()->withErrors(['code' => 'Der Code ist ungültig oder abgelaufen.']);
        }

        return $this->centralLogin->completeLogin($request, $user);
    }

    public function resend(Request $request): RedirectResponse
    {
        $user = $this->challengedUser($request);

        if ($user === null) {
            return redirect()->route('central.login');
        }

        $isSent = EmailAuthentication::make()->sendCode($user);

        return back()->with($isSent ? 'success' : 'error', $isSent
            ? 'Wir haben Ihnen einen neuen Code gesendet.'
            : 'Bitte warten Sie einen Moment, bevor Sie einen neuen Code anfordern.');
    }

    private function isValidCode(User $user, string $code): bool
    {
        if (filled($user->getAppAuthenticationSecret())) {
            $appAuthentication = AppAuthentication::make()->recoverable();

            return $appAuthentication->verifyCode($code, $user->getAppAuthenticationSecret(), shouldPreventCodeReuse: true)
                || $appAuthentication->verifyRecoveryCode($code, $user);
        }

        return EmailAuthentication::make()->verifyCode($code, $user);
    }

    private function challengedUser(Request $request): ?User
    {
        $challenge = $request->session()->get('login_challenge');

        if (! is_array($challenge) || $challenge['expires_at'] < now()->getTimestamp()) {
            return null;
        }

        return User::query()->find($challenge['user_id']);
    }
}
