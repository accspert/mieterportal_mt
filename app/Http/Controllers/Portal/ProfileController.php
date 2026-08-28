<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Portal\Concerns\ResolvesUserProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    use ResolvesUserProfile;

    public function show(): View
    {
        /** @var User $user */
        $user = Auth::user();
        $tenant = $this->resolveRenter($user);
        $owner = $this->resolveOwner($user);

        return view('portal.profile', compact('user', 'tenant', 'owner'));
    }

    public function updateContact(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $tenant = $this->resolveRenter($user);
        $owner = $this->resolveOwner($user);

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'phone' => ['nullable', 'string', 'max:50'],
        ]);

        $fullName = trim($validated['first_name'].' '.$validated['last_name']);

        $user->update([
            'name' => $fullName,
            'email' => $validated['email'],
        ]);

        if ($tenant) {
            $tenant->update([
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
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
