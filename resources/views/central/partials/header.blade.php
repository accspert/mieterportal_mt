{{-- Absolute central URLs: this header also appears on the legal pages of the tenant domains. --}}
@php($home = \App\Services\CentralLoginService::centralUrl('/'))
<header class="site-header" data-header>
    <div class="wrap">
        <a class="wordmark" href="{{ $home }}" aria-label="Liegenschaftsportal – Startseite">
            <span class="dot" aria-hidden="true"></span>Liegenschaftsportal
        </a>
        <nav class="nav" aria-label="Hauptnavigation">
            <a href="{{ $home }}#funktionen">Funktionen</a>
            <a href="{{ $home }}#ablauf">Ablauf</a>
            <a href="{{ $home }}#sicherheit">Sicherheit</a>
            <a href="{{ $home }}#kontakt">Kontakt</a>
            <a class="nav-login" href="{{ \App\Services\CentralLoginService::loginUrl() }}">Login</a>
        </nav>
    </div>
</header>
