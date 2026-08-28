<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            $user = Auth::user();
            if ($user->hasRole('Administrator') || $user->hasRole('Sachbearbeiter')) {
                return $this->adminRedirect();
            }

            return redirect()->route('portal.dashboard');
        }

        $demoEmails = [
            'tenant' => 'tenant@kundenportal.local',
            'owner' => 'owner@kundenportal.local',
            'staff' => 'staff@kundenportal.local',
            'admin' => 'admin@kundenportal.local',
        ];

        $found = User::whereIn('email', $demoEmails)->get()->keyBy('email');
        $demoUsers = array_map(fn (string $email) => $found->get($email), $demoEmails);

        return view('portal.auth.login', compact('demoUsers'));
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            /** @var User $user */
            $user = Auth::user();

            if ($user->hasRole('Administrator') || $user->hasRole('Sachbearbeiter')) {
                return redirect()->intended($this->adminPath());
            }

            return redirect()->intended(route('portal.dashboard'));
        }

        return back()->withErrors([
            'email' => 'Die angegebenen Anmeldedaten sind ungültig.',
        ])->onlyInput('email');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('portal.login')->with('success', 'Sie wurden erfolgreich abgemeldet.');
    }

    /**
     * The Filament admin panel base path.
     * Centralised here so it only needs changing in one place.
     */
    private function adminPath(): string
    {
        return config('filament.path', '/admin');
    }

    private function adminRedirect(): RedirectResponse
    {
        return redirect($this->adminPath());
    }
}
