@extends('central.layout')

@section('title', 'Passwort vergessen')
@section('robots', 'noindex, follow')

@section('content')
    <div class="grid">
        <div class="page-aside">
            <p class="eyebrow"><span class="dot" aria-hidden="true"></span>Login</p>
            <h1 class="page-title">Passwort <span class="serif">vergessen?</span></h1>
            <p class="page-intro">Kein Problem. Wir senden Ihnen einen Link, mit dem Sie ein neues Passwort festlegen.</p>
        </div>

        <div class="page-main">
            <div class="form-card">
                @if (session('success'))
                    <p class="alert alert-success" role="status">{{ session('success') }}</p>
                @endif

                @if ($errors->any())
                    <p class="alert alert-error" role="alert">{{ $errors->first() }}</p>
                @endif

                <form method="POST" action="{{ route('central.password.email') }}">
                    @csrf
                    <div class="field">
                        <label for="email">E-Mail-Adresse</label>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
                    </div>

                    <button class="btn btn-block" type="submit">Link anfordern <span class="arrow" aria-hidden="true">→</span></button>
                </form>
            </div>

            <p class="form-note"><a href="{{ route('central.login') }}">Zurück zum Login</a></p>
        </div>
    </div>
@endsection
