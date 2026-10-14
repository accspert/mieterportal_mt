<!DOCTYPE html>
<html lang="de-CH">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') — Liegenschaftsportal</title>
    <meta name="robots" content="@yield('robots', 'index, follow')">
    <meta name="theme-color" content="#f3f1ec">
    @include('partials.favicons')
    <link rel="stylesheet" href="{{ asset('css/landing.css') }}?v={{ filemtime(public_path('css/landing.css')) }}">
</head>
<body>
<a class="skip-link" href="#inhalt">Zum Inhalt springen</a>
@include('central.partials.backdrop')

@include('central.partials.header')

<main id="inhalt" class="page">
    <div class="wrap">
        @yield('content')
    </div>
</main>

@include('central.partials.footer')

<script>
    (() => {
        const header = document.querySelector('[data-header]');
        const onScroll = () => header.classList.toggle('is-scrolled', window.scrollY > 8);
        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });
    })();
</script>
@stack('scripts')
</body>
</html>
