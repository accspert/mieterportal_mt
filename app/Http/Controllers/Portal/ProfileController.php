<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(): View
    {
        /** @var User $user */
        $user = Auth::user();
        $tenant = $user->tenant;
        $owner = $user->owner;

        return view('portal.profile', compact('user', 'tenant', 'owner'));
    }

    public function updateContact(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $tenant = $user->tenant;
        $owner = $user->owner;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'phone' => ['nullable', 'string', 'max:50'],
        ]);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        if ($tenant) {
            $nameParts = explode(' ', $validated['name'], 2);
            $tenant->update([
                'first_name' => $nameParts[0],
                'last_name' => $nameParts[1] ?? $tenant->last_name,
                'email' => $validated['email'],
                'phone' => $validated['phone'],
            ]);
        } elseif ($owner) {
            $owner->update([
                'email' => $validated['email'],
                'phone' => $validated['phone'],
            ]);
        }

        return redirect()->route('portal.profile.show')
            ->with('success', 'Ihre Kontaktdaten wurden erfolgreich aktualisiert.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()->route('portal.profile.show')
            ->with('success', 'Ihr Passwort wurde sicher geändert.');
    }
}
