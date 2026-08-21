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
                return redirect('/admin');
            }

            return redirect()->route('portal.dashboard');
        }

        $demoUsers = [
            'tenant' => User::where('email', 'tenant@kundenportal.local')->first(),
            'owner' => User::where('email', 'owner@kundenportal.local')->first(),
            'staff' => User::where('email', 'staff@kundenportal.local')->first(),
            'admin' => User::where('email', 'admin@kundenportal.local')->first(),
        ];

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
                return redirect()->intended('/admin');
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
}
