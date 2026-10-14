@extends('central.layout')

@section('title', $isInvitation ? 'Passwort festlegen' : 'Neues Passwort')
@section('robots', 'noindex, nofollow')

@section('content')
    <div class="grid">
        <div class="page-aside">
            <p class="eyebrow"><span class="dot" aria-hidden="true"></span>{{ $isInvitation ? 'Einladung' : 'Passwort' }}</p>
            @if ($isInvitation)
                <h1 class="page-title">Willkommen <span class="serif">an Bord.</span></h1>
                <p class="page-intro">Legen Sie Ihr persönliches Passwort fest. Danach melden Sie sich mit Ihrer E-Mail-Adresse und diesem Passwort an.</p>
            @else
                <h1 class="page-title">Neues <span class="serif">Passwort.</span></h1>
                <p class="page-intro">Bitte wählen Sie ein neues Passwort für Ihr Konto.</p>
            @endif
        </div>

        <div class="page-main">
            <div class="form-card">
                @if ($errors->any())
                    <div class="alert alert-error" role="alert">
                        <div>
                            @foreach ($errors->all() as $error)
                                <div>{{ $error }}</div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <form method="POST" action="{{ route('portal.password.update') }}">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">
                    @if ($isInvitation)
                        <input type="hidden" name="invitation" value="1">
                    @endif

                    <div class="field">
                        <label for="email">E-Mail-Adresse</label>
                        <input type="email" id="email" name="email" value="{{ old('email', $email) }}" required autocomplete="username">
                    </div>

                    <div class="field">
                        <label for="password">Passwort</label>
                        <x-password-input id="password" name="password" required autofocus autocomplete="new-password" />
                        <span class="form-note">Mindestens 10 Zeichen, mit Buchstaben und Zahlen.</span>
                    </div>

                    <div class="field">
                        <label for="password_confirmation">Passwort bestätigen</label>
                        <x-password-input id="password_confirmation" name="password_confirmation" required autocomplete="new-password" />
                    </div>

                    <button class="btn btn-block" type="submit">Passwort speichern <span class="arrow" aria-hidden="true">→</span></button>
                </form>
            </div>
        </div>
    </div>
@endsection
