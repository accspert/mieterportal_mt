<footer class="site-footer">
    <div class="wrap">
        <div class="footer-row">
            <span>&copy; {{ date('Y') }} {{ config('legal.company') }}</span>
            <nav aria-label="Rechtliches">
                <a class="link-underline" href="{{ route('legal.imprint') }}">Impressum</a>
                <a class="link-underline" href="{{ route('legal.privacy') }}">Datenschutz</a>
                <a class="link-underline" href="{{ route('legal.terms') }}">AGB</a>
            </nav>
        </div>
        <p class="footer-mark" aria-hidden="true">Liegenschaftsportal<span class="dot"></span></p>
    </div>
</footer>
