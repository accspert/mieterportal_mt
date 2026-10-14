@extends('central.layout')

@section('title', 'Kostenlos testen')
@section('robots', 'noindex, follow')

@section('content')
    <div class="grid">
        <div class="page-aside">
            <p class="eyebrow"><span class="dot" aria-hidden="true"></span>Registrierung</p>
            <h1 class="page-title">In zwei Minuten <span class="serif">startklar.</span></h1>
            <p class="page-intro">Registrieren Sie Ihre Verwaltung, bestätigen Sie Ihre E-Mail-Adresse – wir richten alles Weitere automatisch ein.</p>
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

                <form method="POST" action="{{ route('central.register.store') }}" novalidate>
                    @csrf

                    <fieldset class="plans">
                        <legend class="field-label" style="margin-bottom: 10px;">Paket</legend>
                        @foreach ($plans as $planKey => $plan)
                            <label class="plan">
                                <input type="radio" name="plan" value="{{ $planKey }}" @checked($selectedPlan === $planKey) @disabled(! $plan['available'])>
                                <span class="plan-head">
                                    <span class="plan-name">{{ $plan['name'] }}</span>
                                    <span class="plan-price">{{ $plan['price'] }}</span>
                                </span>
                                <p>{{ $plan['description'] }}</p>
                                <ul>
                                    @foreach ($plan['features'] as $feature)
                                        <li>{{ $feature }}</li>
                                    @endforeach
                                </ul>
                            </label>
                        @endforeach
                    </fieldset>

                    <div class="field">
                        <label for="company_name">Name der Verwaltung</label>
                        <input type="text" id="company_name" name="company_name" value="{{ old('company_name') }}" required autocomplete="organization">
                    </div>

                    <div class="field">
                        <label for="admin_name">Ihr Name</label>
                        <input type="text" id="admin_name" name="admin_name" value="{{ old('admin_name') }}" required autocomplete="name">
                    </div>

                    <div class="field">
                        <label for="email">E-Mail-Adresse</label>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" required autocomplete="email">
                    </div>

                    <div class="field">
                        <label for="phone">Telefon <span style="text-transform: none; font-weight: 400;">(optional)</span></label>
                        <input type="tel" id="phone" name="phone" value="{{ old('phone') }}" autocomplete="tel">
                    </div>

                    <div class="honeypot" aria-hidden="true">
                        <label for="website">Website</label>
                        <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
                    </div>

                    <label class="check">
                        <input type="checkbox" name="terms" value="1" @checked(old('terms')) required>
                        <span>Ich akzeptiere die <a href="{{ route('legal.terms') }}" target="_blank">AGB</a> und habe die <a href="{{ route('legal.privacy') }}" target="_blank">Datenschutzerklärung</a> gelesen.</span>
                    </label>

                    <button class="btn btn-block" type="submit">Kostenlos registrieren <span class="arrow" aria-hidden="true">→</span></button>
                </form>
            </div>

            <p class="form-note">Bereits registriert? <a href="{{ route('central.login') }}">Zum Login</a></p>
        </div>
    </div>
@endsection
