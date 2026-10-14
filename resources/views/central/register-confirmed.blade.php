@extends('central.layout')

@section('title', 'Registrierung bestätigt')
@section('robots', 'noindex, nofollow')

@section('content')
    <div class="grid">
        <div class="page-aside">
            <p class="eyebrow"><span class="dot" aria-hidden="true"></span>Registrierung · Schritt 3 von 3</p>
            <h1 class="page-title">Danke, <span class="serif">bestätigt.</span></h1>
            <p class="page-intro">
                Wir richten {{ $registration->company_name }} gerade ein. In wenigen Minuten erhalten Sie eine E-Mail,
                mit der Sie Ihr persönliches Passwort festlegen.
            </p>
        </div>

        <div class="page-main">
            <div class="form-card">
                <p class="form-note" style="margin: 0 0 20px;">Danach melden Sie sich jederzeit hier an:</p>
                <a class="btn btn-block" href="{{ route('central.login') }}">Zum Login <span class="arrow" aria-hidden="true">→</span></a>
            </div>
        </div>
    </div>
@endsection
