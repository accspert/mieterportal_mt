@extends('central.layout')

@section('title', 'E-Mail bestätigen')
@section('robots', 'noindex, nofollow')

@section('content')
    <div class="grid">
        <div class="page-aside">
            <p class="eyebrow"><span class="dot" aria-hidden="true"></span>Registrierung · Schritt 2 von 3</p>
            <h1 class="page-title">Schauen Sie in Ihr <span class="serif">Postfach.</span></h1>
            <p class="page-intro">
                Wir haben Ihnen eine E-Mail
                @if (session('registration_email'))
                    an <strong>{{ session('registration_email') }}</strong>
                @endif
                gesendet. Bitte bestätigen Sie darin Ihre Registrierung – danach richten wir Ihre Verwaltung ein.
            </p>
        </div>

        <div class="page-main">
            <div class="form-card">
                <p class="form-note" style="margin: 0;">Keine E-Mail erhalten? Prüfen Sie Ihren Spam-Ordner. Der Bestätigungslink ist 48 Stunden gültig.</p>
            </div>
        </div>
    </div>
@endsection
