@extends('central.layout')

@section('title', 'Login')
@section('robots', 'noindex, follow')

@section('content')
    <div class="grid">
        <div class="page-aside">
            <p class="eyebrow"><span class="dot" aria-hidden="true"></span>Login</p>
            <h1 class="page-title">Willkommen <span class="serif">zurück.</span></h1>
            <p class="page-intro">Ein Login für alle: Verwaltungen, Sachbearbeitende, Mieterinnen, Mieter und Eigentümer melden sich hier an.</p>
        </div>

        <div class="page-main">
            <div class="form-card">
                @if ($notice)
                    <p class="alert alert-success" role="status">{{ $notice }}</p>
                @endif

                @if ($errors->any())
                    <p class="alert alert-error" role="alert">{{ $errors->first() }}</p>
                @endif

                <form method="POST" action="{{ route('central.login.attempt') }}">
                    @csrf
                    <div class="field">
                        <label for="email">E-Mail-Adresse</label>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
                    </div>

                    <div class="field">
                        <div class="field-row">
                            <label for="password">Passwort</label>
                            <a href="{{ route('central.password.request') }}">Passwort vergessen?</a>
                        </div>
                        <x-password-input id="password" name="password" required autocomplete="current-password" />
                    </div>

                    <button class="btn btn-block" type="submit">Anmelden <span class="arrow" aria-hidden="true">→</span></button>
                </form>
            </div>

            <p class="form-note">Noch kein Konto? <a href="{{ route('central.register') }}">Jetzt kostenlos testen</a></p>
        </div>
    </div>
@endsection
