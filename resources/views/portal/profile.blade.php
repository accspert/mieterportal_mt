@extends('portal.layout')

@section('title', 'Mein Profil & Sicherheit')
@section('page-title', 'Mein Profil & Einstellungen')

@section('content')
<div style="max-width: 800px; margin: 0 auto;">
    <div style="margin-bottom: 24px;">
        <h1 style="font-size: 24px; margin-bottom: 6px;">Kontoeinstellungen & Sicherheit</h1>
        <p style="color: var(--text-muted); font-size: 14px;">Verwalten Sie Ihre persönlichen Kontaktdaten und Anmeldeinformationen.</p>
    </div>

    <!-- Contact Info Card -->
    <div class="card" style="margin-bottom: 24px;">
        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 14px;">
            <div style="width: 40px; height: 40px; border-radius: var(--radius-md); background: rgba(37, 99, 235, 0.15); color: #3b82f6; display: flex; align-items: center; justify-content: center;">
                <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 20px; height: 20px;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                </svg>
            </div>
            <div>
                <h3 style="font-size: 18px;">Persönliche Kontaktdaten</h3>
                <div style="font-size: 12px; color: var(--text-muted);">Aktualisieren Sie Ihren Namen, E-Mail-Adresse und Telefonnummer.</div>
            </div>
        </div>

        <form method="POST" action="{{ route('portal.profile.contact') }}">
            @csrf
            @method('PUT')

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px;">
                <div class="form-group">
                    <label class="form-label" for="name">Vollständiger Name *</label>
                    <input type="text" id="name" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="email">E-Mail-Adresse *</label>
                    <input type="email" id="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="phone">Telefon- / Mobilnummer (für Rückfragen)</label>
                <input type="text" id="phone" name="phone" class="form-control" placeholder="+49 170 1234567" value="{{ old('phone', $tenant?->phone ?? $owner?->phone) }}">
            </div>

            <div style="display: flex; justify-content: flex-end; margin-top: 16px;">
                <button type="submit" class="btn btn-primary">
                    <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                    </svg>
                    Kontaktdaten speichern
                </button>
            </div>
        </form>
    </div>

    <!-- Security & Password Card -->
    <div class="card">
        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 14px;">
            <div style="width: 40px; height: 40px; border-radius: var(--radius-md); background: rgba(239, 68, 68, 0.15); color: #ef4444; display: flex; align-items: center; justify-content: center;">
                <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 20px; height: 20px;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                </svg>
            </div>
            <div>
                <h3 style="font-size: 18px;">Passwort & Sicherheit</h3>
                <div style="font-size: 12px; color: var(--text-muted);">Ändern Sie Ihr Passwort, um Ihr Kundenkonto zu schützen.</div>
            </div>
        </div>

        <form method="POST" action="{{ route('portal.profile.password') }}">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label class="form-label" for="current_password">Aktuelles Passwort *</label>
                <input type="password" id="current_password" name="current_password" class="form-control" required>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px;">
                <div class="form-group">
                    <label class="form-label" for="password">Neues Passwort *</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="password_confirmation">Neues Passwort bestätigen *</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" required>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; margin-top: 16px;">
                <button type="submit" class="btn btn-secondary">
                    <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                    </svg>
                    Passwort ändern
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
