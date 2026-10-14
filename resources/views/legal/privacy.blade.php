@extends('legal.layout')

@section('title', 'Datenschutzerklärung')

@section('legal')
    <p>
        Mit dieser Datenschutzerklärung informieren wir, welche Personendaten wir im Zusammenhang mit dem Liegenschaftsportal
        bearbeiten, zu welchen Zwecken und welche Rechte Sie haben. Massgebend ist das Schweizer Bundesgesetz über den
        Datenschutz (DSG). Soweit im Einzelfall die Datenschutz-Grundverordnung der EU (DSGVO) anwendbar ist, gelten
        ergänzend deren Bestimmungen.
    </p>

    <h2>1. Verantwortliche Stelle</h2>
    @include('legal.partials.operator-address')

    <h2>2. Wer für welche Daten verantwortlich ist</h2>
    <p>
        Das Liegenschaftsportal ist eine Software, die Liegenschaftsverwaltungen, Vermieterinnen und Vermieter sowie
        Stockwerkeigentümergemeinschaften (nachfolgend «Verwaltungen») für die Kommunikation mit ihren Mieterinnen,
        Mietern und Eigentümern nutzen. Jede Verwaltung erhält einen eigenen, von anderen Verwaltungen getrennten Bereich
        mit eigener Datenbank.
    </p>
    <ul>
        <li>
            <strong>Daten in den Portalen der Verwaltungen</strong> (z. B. Mieterdaten, Verträge, Dokumente, Rechnungen,
            Schadenmeldungen, Nachrichten): Verantwortlich ist die jeweilige Verwaltung. Wir bearbeiten diese Daten
            ausschliesslich in ihrem Auftrag und nach ihren Weisungen als Auftragsbearbeiterin. Anfragen zu diesen Daten
            richten Sie bitte direkt an Ihre Verwaltung.
        </li>
        <li>
            <strong>Daten unserer Kundinnen und Kunden</strong> (Verwaltungen, deren Ansprechpersonen und
            Vertragsdaten) sowie technische Daten zum Betrieb der Plattform: Hier sind wir verantwortlich.
        </li>
    </ul>

    <h2>3. Welche Daten wir bearbeiten</h2>
    <ul>
        <li><strong>Konto- und Anmeldedaten:</strong> Name, E-Mail-Adresse, Rolle, Passwort (nur verschlüsselt als Hash gespeichert), Angaben zur Zwei-Faktor-Anmeldung.</li>
        <li><strong>Inhaltsdaten:</strong> Daten, die Verwaltungen und Nutzende im Portal erfassen oder hochladen.</li>
        <li><strong>Vertragsdaten unserer Kundschaft:</strong> Firmenname, Ansprechpersonen, Kontaktdaten, gewähltes Abonnement.</li>
        <li><strong>Technische Daten:</strong> IP-Adresse, Datum und Uhrzeit des Zugriffs, aufgerufene Seiten, Browser- und Geräteinformationen sowie Fehlermeldungen.</li>
        <li><strong>Protokolldaten:</strong> Änderungen an Datensätzen im Verwaltungsbereich werden mit Zeitpunkt und handelnder Person protokolliert, um Nachvollziehbarkeit und Sicherheit zu gewährleisten.</li>
    </ul>

    <h2>4. Zwecke der Bearbeitung</h2>
    <ul>
        <li>Bereitstellung und Betrieb des Liegenschaftsportals und Erfüllung unserer Verträge mit den Verwaltungen;</li>
        <li>Anmeldung, Zugangsschutz und Missbrauchsverhinderung (z. B. Begrenzung von Anmeldeversuchen);</li>
        <li>Versand von Systemnachrichten per E-Mail (z. B. Einladungen, Passwort-Zurücksetzung, Benachrichtigungen zu Tickets, Dokumenten und Rechnungen);</li>
        <li>Erkennung und Behebung technischer Fehler sowie Gewährleistung der Datensicherheit;</li>
        <li>Erfüllung gesetzlicher Pflichten, insbesondere Aufbewahrungspflichten.</li>
    </ul>

    <h2>5. Cookies und lokale Speicherung</h2>
    <p>Wir verwenden ausschliesslich technisch notwendige Cookies und keine Tracking- oder Werbe-Cookies:</p>
    <ul>
        <li><strong>Sitzungs-Cookie</strong> – hält Sie während der Nutzung angemeldet; wird beim Abmelden bzw. nach Ablauf der Sitzung ungültig.</li>
        <li><strong>XSRF-TOKEN</strong> – schützt Formulare vor gefälschten Anfragen (Cross-Site Request Forgery).</li>
        <li><strong>Sprach-Cookie (locale)</strong> – speichert die gewählte Sprache für bis zu ein Jahr.</li>
        <li><strong>«Angemeldet bleiben»</strong> – nur wenn Sie diese Option aktiv wählen.</li>
    </ul>
    <p>Die Darstellungseinstellung (hell/dunkel) wird nur lokal in Ihrem Browser gespeichert und nicht an uns übermittelt. Schriftarten werden von unseren eigenen Servern geladen.</p>

    <h2>6. Auftragsbearbeiter und Empfänger</h2>
    <p>Wir geben Personendaten nur weiter, soweit dies für den Betrieb erforderlich ist, und verpflichten unsere Dienstleister vertraglich zur Einhaltung des Datenschutzes:</p>
    <ul>
        <li>
            <strong>Hosting:</strong>
            @if (config('legal.hosting_provider'))
                {{ config('legal.hosting_provider') }}, Serverstandort {{ config('legal.hosting_location') }}.
            @else
                Rechenzentrumsbetreiber mit Serverstandort {{ config('legal.hosting_location') }}.
            @endif
            Dort werden sämtliche Datenbanken und hochgeladenen Dateien gespeichert und gesichert.
        </li>
        <li><strong>E-Mail-Versand:</strong> Ein E-Mail-Dienstleister übermittelt die Systemnachrichten in unserem Auftrag.</li>
        @if (config('sentry.dsn'))
            <li>
                <strong>Fehlerüberwachung:</strong> Wir nutzen Sentry (Functional Software, Inc.), um technische Fehler zu erkennen.
                Übermittelt werden technische Angaben zum Fehler (z. B. betroffene Funktion, Zeitpunkt, Browser) und eine
                interne Kennung der Verwaltung. Namen, E-Mail-Adressen, Formularinhalte und IP-Adressen werden dabei nicht
                übermittelt. Die Daten werden auf Servern in der Europäischen Union verarbeitet.
            </li>
        @endif
    </ul>
    <p>Eine Weitergabe zu Werbezwecken oder ein Verkauf von Daten findet nicht statt.</p>

    <h2>7. Bekanntgabe ins Ausland</h2>
    <p>
        Die Daten der Portale werden in der Schweiz bzw. in Staaten mit einem vom Bundesrat anerkannten angemessenen
        Datenschutzniveau (insbesondere Staaten der EU/des EWR) gespeichert. Sollte im Einzelfall eine Bekanntgabe in
        einen anderen Staat erfolgen, stellen wir einen angemessenen Schutz durch geeignete Garantien sicher,
        insbesondere durch die Standardvertragsklauseln der EU-Kommission in der vom EDÖB anerkannten Fassung.
    </p>

    <h2>8. Aufbewahrung und Löschung</h2>
    <p>
        Wir bearbeiten Personendaten nur so lange, wie es für die genannten Zwecke erforderlich ist oder gesetzliche
        Aufbewahrungspflichten bestehen. Nach Ende des Vertrags mit einer Verwaltung werden deren Daten gemäss den
        vertraglichen Vereinbarungen herausgegeben und anschliessend gelöscht; Datensicherungen werden nach Ablauf
        ihrer Aufbewahrungsfrist automatisch überschrieben. Technische Protokolle werden in der Regel nach höchstens
        90 Tagen gelöscht.
    </p>

    <h2>9. Datensicherheit</h2>
    <p>Wir treffen angemessene technische und organisatorische Massnahmen zum Schutz Ihrer Daten, unter anderem:</p>
    <ul>
        <li>verschlüsselte Übertragung (HTTPS/TLS);</li>
        <li>getrennte Datenbanken und Dateispeicher je Verwaltung;</li>
        <li>Zwei-Faktor-Anmeldung für den Verwaltungsbereich;</li>
        <li>rollenbasierte Zugriffsrechte und Protokollierung von Änderungen;</li>
        <li>regelmässige, verschlüsselt abgelegte Datensicherungen.</li>
    </ul>

    <h2>10. Ihre Rechte</h2>
    <p>Im Rahmen des anwendbaren Datenschutzrechts haben Sie insbesondere das Recht,</p>
    <ul>
        <li>Auskunft über die zu Ihrer Person bearbeiteten Daten zu verlangen;</li>
        <li>unrichtige Daten berichtigen zu lassen;</li>
        <li>die Löschung oder Einschränkung der Bearbeitung zu verlangen, soweit keine Aufbewahrungspflichten entgegenstehen;</li>
        <li>die Herausgabe Ihrer Daten in einem gängigen elektronischen Format zu verlangen;</li>
        <li>einer Bearbeitung zu widersprechen bzw. eine erteilte Einwilligung zu widerrufen.</li>
    </ul>
    <p>
        Betrifft Ihr Anliegen Daten im Portal Ihrer Verwaltung, wenden Sie sich bitte an diese; wir unterstützen die
        Verwaltung bei der Bearbeitung. Andernfalls erreichen Sie uns unter der oben genannten Adresse. Sie haben zudem
        das Recht, sich beim Eidgenössischen Datenschutz- und Öffentlichkeitsbeauftragten (EDÖB, www.edoeb.admin.ch)
        bzw. bei der zuständigen Aufsichtsbehörde Ihres Wohnsitzstaates zu beschweren.
    </p>

    <h2>11. Änderungen</h2>
    <p>
        Wir können diese Datenschutzerklärung anpassen, wenn sich unsere Datenbearbeitungen oder die Rechtslage ändern.
        Es gilt die jeweils auf dieser Seite veröffentlichte Fassung.
    </p>
@endsection
