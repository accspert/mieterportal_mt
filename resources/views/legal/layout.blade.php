@extends('central.layout')

@section('content')
    <div class="grid">
        <div class="legal-aside">
            <div class="sticky">
                <p class="eyebrow"><span class="dot" aria-hidden="true"></span>Rechtliches</p>
                <h1 class="page-title">@yield('title')</h1>
                <p class="legal-meta">Stand: {{ config('legal.last_updated') }}</p>
            </div>
        </div>

        <article class="legal-body">
            @yield('legal')
        </article>
    </div>
@endsection
