@php
    $contactEmail = config('legal.email');
@endphp
<!DOCTYPE html>
<html lang="de-CH">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Liegenschaftsportal — Die digitale Schnittstelle zwischen Verwaltung und Mieterschaft</title>
    <meta name="description" content="Das Liegenschaftsportal für Verwaltungen, Vermieter und Stockwerkeigentümer in der Schweiz: Schadenmeldungen, Dokumente, Rechnungen und Mitteilungen an einem Ort.">
    <meta name="theme-color" content="#f3f1ec">
    <meta property="og:title" content="Liegenschaftsportal — Weniger Telefon. Mehr Zeit.">
    <meta property="og:description" content="Schadenmeldungen, Dokumente, Rechnungen und Mitteilungen an einem Ort – für Schweizer Liegenschaftsverwaltungen.">
    <meta property="og:type" content="website">
    <link rel="canonical" href="{{ url('/') }}">
    @include('partials.favicons')
    <link rel="stylesheet" href="{{ asset('css/landing.css') }}?v={{ filemtime(public_path('css/landing.css')) }}">
    <script>document.documentElement.classList.add('js');</script>
</head>
<body>
<a class="skip-link" href="#inhalt">Zum Inhalt springen</a>
@include('central.partials.backdrop')

@include('central.partials.header')

<main id="inhalt">
    {{-- Hero --}}
    <section class="hero" aria-labelledby="hero-title">
        <div class="wrap">
            <p class="eyebrow"><span class="dot" aria-hidden="true"></span>Software für Liegenschaftsverwaltungen</p>
            <div class="hero-head">
                <h1 class="hero-title" id="hero-title">
                    <span class="line"><span>Weniger Telefon.</span></span>
                    <span class="line"><span><span class="serif">Mehr</span> Zeit.</span></span>
                </h1>
                <div class="hero-actions">
                    <a class="btn btn-large" href="{{ route('central.register') }}">Jetzt kostenlos testen <span class="arrow" aria-hidden="true">→</span></a>
                </div>
            </div>

            <div class="grid">
                <div class="hero-copy">
                    <p class="hero-lead">
                        Das Liegenschaftsportal für Verwaltungen, Vermieter und Stockwerkeigentümer­gemeinschaften.
                        Schadenmeldungen, Dokumente, Rechnungen und Mitteilungen laufen an einem Ort zusammen –
                        nachvollziehbar für beide Seiten, rund um die Uhr erreichbar.
                    </p>
                </div>

                <div class="hero-visual">
                    <figure class="ticket" aria-label="Beispiel einer Schadenmeldung im Liegenschaftsportal">
                        <div class="ticket-head">
                            <span>Ticket 2041</span>
                            <span class="ticket-priority"><span class="dot" aria-hidden="true"></span>Hoch</span>
                        </div>
                        <div class="ticket-body">
                            <p class="ticket-title">Waschmaschine in der Waschküche läuft aus</p>
                            <p class="ticket-meta">Haus B · Untergeschoss · gemeldet über das Portal</p>
                            <div class="ticket-photo" aria-hidden="true">foto_waschkueche.jpg · 2,4 MB</div>
                        </div>
                        <ol class="ticket-steps">
                            <li>Gemeldet <time>07:42</time></li>
                            <li>Zugewiesen <time>08:15</time></li>
                            <li>In Bearbeitung <time>09:30</time></li>
                            <li>Erledigt <time>14:05</time></li>
                        </ol>
                        <figcaption class="ticket-toast" aria-hidden="true">
                            <span class="dot"></span>E-Mail an die Mieterin versendet
                        </figcaption>
                    </figure>
                </div>
            </div>

            <ul class="hero-facts" role="list">
                <li><strong>Eine Datenbank pro Verwaltung</strong>Ihre Daten sind von allen anderen Kunden getrennt.</li>
                <li><strong>Zwei-Faktor-Anmeldung</strong>Für alle Zugänge der Verwaltung.</li>
                <li><strong>Ohne Tracking</strong>Keine Werbe- oder Analyse-Cookies.</li>
                <li><strong>Deutsch &amp; Englisch</strong>Für eine internationale Mieterschaft.</li>
            </ul>
        </div>

        <div class="marquee" aria-hidden="true">
            <div class="marquee-track">
                @foreach (range(1, 2) as $copy)
                    <span>Schadenmeldung mit Foto</span>
                    <span>Mietvertrag als PDF</span>
                    <span>Rechnungen &amp; Abrechnungen</span>
                    <span>Aushänge</span>
                    <span>Nachrichten an die Verwaltung</span>
                    <span>Aufgaben &amp; Zuständigkeiten</span>
                    <span>Änderungsprotokoll</span>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Features --}}
    <section class="section" id="funktionen" aria-labelledby="funktionen-title">
        <div class="wrap">
            <div class="grid split">
                <div class="section-head">
                    <div class="sticky">
                        <span class="section-index">01 — Für die Mieterschaft</span>
                        <h2 class="section-title" id="funktionen-title" data-reveal>Alles zur Wohnung. <span class="serif">Ohne</span> Warteschlaufe.</h2>
                        <p class="section-intro" data-reveal>Mieterinnen und Mieter erledigen ihre Anliegen dann, wenn es ihnen passt – und sehen jederzeit, wo ihr Anliegen steht.</p>
                    </div>
                </div>
                <ol class="feature-list">
                    <li data-reveal><h3>Schäden melden</h3><p>Mit Beschreibung und Fotos, direkt vom Smartphone. Der Status ist jederzeit sichtbar.</p></li>
                    <li data-reveal><h3>Dokumente abrufen</h3><p>Mietvertrag, Abrechnungen und weitere Unterlagen stehen digital bereit.</p></li>
                    <li data-reveal><h3>Rechnungen einsehen</h3><p>Offene und bezahlte Rechnungen im Überblick, als PDF zum Herunterladen.</p></li>
                    <li data-reveal><h3>Nachrichten schreiben</h3><p>Ein direkter Draht zur Verwaltung – mit Verlauf statt verstreuter E-Mails.</p></li>
                    <li data-reveal><h3>Aushänge lesen</h3><p>Mitteilungen zur Liegenschaft erreichen alle, nicht nur das Treppenhaus.</p></li>
                </ol>
            </div>

            <div class="grid split">
                <div class="section-head">
                    <div class="sticky">
                        <span class="section-index">02 — Für die Verwaltung</span>
                        <h2 class="section-title" data-reveal>Ein Cockpit für <span class="serif">jede</span> Liegenschaft.</h2>
                        <p class="section-intro" data-reveal>Weniger Rückfragen, klare Zuständigkeiten und eine lückenlose Dokumentation – für Administratoren und Sachbearbeitende.</p>
                    </div>
                </div>
                <ol class="feature-list">
                    <li data-reveal><h3>Objekte und Personen</h3><p>Liegenschaften, Einheiten, Mieter und Eigentümer an einem Ort verwalten.</p></li>
                    <li data-reveal><h3>Tickets steuern</h3><p>Meldungen priorisieren, zuweisen und abschliessen. Die Mieterschaft wird automatisch informiert.</p></li>
                    <li data-reveal><h3>Dokumente und Rechnungen bereitstellen</h3><p>Einmal hochladen, gezielt freigeben – ohne Briefpost.</p></li>
                    <li data-reveal><h3>Aufgaben verteilen</h3><p>Zuständigkeiten im Team festlegen und den Überblick behalten.</p></li>
                    <li data-reveal><h3>Rollen und Protokoll</h3><p>Abgestufte Rechte und ein Änderungsprotokoll machen jede Anpassung nachvollziehbar.</p></li>
                    <li data-reveal><h3>Export</h3><p>Daten jederzeit exportieren und weiterverarbeiten.</p></li>
                </ol>
            </div>
        </div>
    </section>

    {{-- Process --}}
    <section class="section" id="ablauf" aria-labelledby="ablauf-title">
        <div class="wrap">
            <div class="grid">
                <div class="section-head" style="grid-column: 1 / span 7;">
                    <span class="section-index">03 — Ablauf</span>
                    <h2 class="section-title" id="ablauf-title" data-reveal>Vom tropfenden Hahn zum <span class="serif">erledigten</span> Ticket.</h2>
                </div>
            </div>
            <ol class="process">
                <li data-reveal><span class="process-number">01</span><h3>Melden</h3><p>Die Mieterin beschreibt den Schaden im Portal und lädt Fotos hoch.</p></li>
                <li data-reveal style="--reveal-delay: .08s"><span class="process-number">02</span><h3>Zuweisen</h3><p>Die Verwaltung sieht die Meldung sofort, setzt die Priorität und weist sie zu.</p></li>
                <li data-reveal style="--reveal-delay: .16s"><span class="process-number">03</span><h3>Informieren</h3><p>Jede Statusänderung geht automatisch per E-Mail an die Mieterschaft.</p></li>
                <li data-reveal style="--reveal-delay: .24s"><span class="process-number">04</span><h3>Abschliessen</h3><p>Erledigt und dokumentiert – nachvollziehbar auch Monate später.</p></li>
            </ol>
        </div>
    </section>

    {{-- Security --}}
    <section class="section section-dark" id="sicherheit" aria-labelledby="sicherheit-title">
        <div class="wrap">
            <div class="grid">
                <div class="section-head" style="grid-column: 1 / span 8;">
                    <span class="section-index">04 — Sicherheit &amp; Datenschutz</span>
                    <h2 class="section-title" id="sicherheit-title" data-reveal>Mieterdaten sind <span class="serif">Vertrauenssache.</span></h2>
                    <p class="section-intro" data-reveal>Das Liegenschaftsportal ist von Grund auf für den Schweizer Datenschutz gebaut – nicht nachträglich angepasst.</p>
                </div>
            </div>

            <div class="figures">
                <div class="figure" data-reveal><span class="figure-value">1</span><p>eigene Datenbank und eigener Dateispeicher pro Verwaltung.</p></div>
                <div class="figure" data-reveal style="--reveal-delay: .08s"><span class="figure-value">2</span><p>Faktoren bei der Anmeldung für alle Zugänge der Verwaltung.</p></div>
                <div class="figure" data-reveal style="--reveal-delay: .16s"><span class="figure-value">0</span><p>Tracking- und Werbe-Cookies. Auch Schriften laden wir vom eigenen Server.</p></div>
                <div class="figure" data-reveal style="--reveal-delay: .24s"><span class="figure-value"><span class="serif">24h</span></span><p>Sichere Server mit täglichen Backups.</p></div>
            </div>

            <ul class="checklist">
                <li data-reveal><strong>Verschlüsselte Übertragung</strong>Jede Verbindung läuft über HTTPS.</li>
                <li data-reveal><strong>Tägliche Backups</strong>Verschlüsselt gesichert und automatisch überwacht.</li>
                <li data-reveal><strong>Nachvollziehbar</strong>Änderungen im Verwaltungsbereich werden protokolliert.</li>
            </ul>
        </div>
    </section>

    {{-- Contact --}}
    <section class="contact" id="kontakt" aria-labelledby="kontakt-title">
        <div class="wrap">
            <p class="eyebrow">05 — Kontakt</p>
            <h2 class="contact-title" id="kontakt-title" data-reveal>Bereit für weniger Telefon?</h2>
            <div class="contact-row">
                <address>
                    {{ config('legal.company') }}<br>
                    {{ config('legal.street') }}<br>
                    {{ config('legal.postal_code') }} {{ config('legal.city') }}, {{ config('legal.country') }}
                    @if ($contactEmail)
                        <br><a class="link-underline" href="mailto:{{ $contactEmail }}">{{ $contactEmail }}</a>
                    @endif
                    @if (config('legal.phone'))
                        <br>{{ config('legal.phone') }}
                    @endif
                </address>
            </div>
        </div>
    </section>
</main>

@include('central.partials.footer')

<script>
    (() => {
        const header = document.querySelector('[data-header]');
        const onScroll = () => header.classList.toggle('is-scrolled', window.scrollY > 8);
        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });

        const revealables = document.querySelectorAll('[data-reveal]');
        if (!('IntersectionObserver' in window)) {
            revealables.forEach((element) => element.classList.add('is-visible'));
            return;
        }

        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        }, { rootMargin: '0px 0px -8% 0px', threshold: 0.12 });

        revealables.forEach((element) => observer.observe(element));
    })();

</script>
</body>
</html>
