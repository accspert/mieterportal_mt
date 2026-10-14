<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Services\CentralLoginService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * There is only one login on the central domain; the tenant domains just redirect there.
 */
class AuthController extends Controller
{
    public function showLogin(): RedirectResponse
    {
        if (Auth::check()) {
            return Auth::user()->hasAnyRole(['Administrator', 'Sachbearbeiter'])
                ? redirect($this->adminPath())
                : redirect()->route('portal.dashboard');
        }

        return redirect()->away(CentralLoginService::loginUrl());
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->away(CentralLoginService::loginUrl(['abgemeldet' => 1]));
    }

    /**
     * The Filament admin panel base path.
     * Centralised here so it only needs changing in one place.
     */
    private function adminPath(): string
    {
        return config('filament.path', '/admin');
    }
}
