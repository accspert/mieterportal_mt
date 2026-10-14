<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\CentralLoginService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

/**
 * Sets a new password from a reset or invitation link. The links are requested on the central
 * login and point to the tenant domain, where the account lives.
 */
class PasswordResetController extends Controller
{
    public function showResetForm(Request $request, string $token): View
    {
        return view('portal.auth.reset-password', [
            'token' => $token,
            'email' => (string) $request->query('email', ''),
            'isInvitation' => $request->boolean('invitation'),
        ]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(10)->letters()->numbers()],
        ]);

        $broker = $request->boolean('invitation') ? 'invitations' : null;

        $status = Password::broker($broker)->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                // Every API token issued before the reset is revoked.
                $user->tokens()->delete();

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return back()->withInput($request->only('email'))->withErrors(['email' => __($status)]);
        }

        return redirect()->away(CentralLoginService::loginUrl(['passwort' => 1]));
    }
}
