@extends('central.layout')

@section('title', 'Anmeldung bestätigen')
@section('robots', 'noindex, nofollow')

@section('content')
    <div class="grid">
        <div class="page-aside">
            <p class="eyebrow"><span class="dot" aria-hidden="true"></span>Login · Zweiter Schritt</p>
            <h1 class="page-title">Kurz <span class="serif">bestätigen.</span></h1>
            <p class="page-intro">
                {{ $usesAuthenticatorApp
                    ? 'Geben Sie den Code aus Ihrer Authenticator-App ein – oder einen Ihrer Wiederherstellungscodes.'
                    : 'Wir haben Ihnen einen Code per E-Mail gesendet. Er ist einige Minuten gültig.' }}
            </p>
        </div>

        <div class="page-main">
            <div class="form-card">
                @if (session('success'))
                    <p class="alert alert-success" role="status">{{ session('success') }}</p>
                @endif

                @if (session('error'))
                    <p class="alert alert-error" role="alert">{{ session('error') }}</p>
                @endif

                @if ($errors->any())
                    <p class="alert alert-error" role="alert">{{ $errors->first() }}</p>
                @endif

                <form method="POST" action="{{ route('auth.challenge.verify') }}">
                    @csrf
                    <div class="field">
                        <label for="code">{{ $usesAuthenticatorApp ? 'Code oder Wiederherstellungscode' : 'Code aus der E-Mail' }}</label>
                        <input type="text" id="code" name="code" required autofocus autocomplete="one-time-code"
                               inputmode="{{ $usesAuthenticatorApp ? 'text' : 'numeric' }}">
                    </div>

                    <button class="btn btn-block" type="submit">Bestätigen <span class="arrow" aria-hidden="true">→</span></button>
                </form>
            </div>

            @unless ($usesAuthenticatorApp)
                <form class="form-note" method="POST" action="{{ route('auth.challenge.resend') }}">
                    @csrf
                    Keine E-Mail erhalten? <button type="submit" class="link-button">Neuen Code senden</button>
                </form>
            @endunless
        </div>
    </div>
@endsection
