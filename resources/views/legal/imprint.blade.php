@extends('legal.layout')

@section('title', 'Impressum')

@section('legal')
    <h2>Betreiberin</h2>
    @include('legal.partials.operator-address')

    <h2>Vertretungsberechtigte Person</h2>
    <p>{{ config('legal.managing_director') }}, Geschäftsführer</p>

    <h2>Handelsregister</h2>
    @if (config('legal.uid'))
        <p>
            Eingetragen im {{ config('legal.commercial_register') }}<br>
            Unternehmens-Identifikationsnummer (UID): {{ config('legal.uid') }}
        </p>
    @else
        <p>Die Gesellschaft befindet sich in Gründung. Die Angaben zum Handelsregistereintrag und zur Unternehmens-Identifikationsnummer (UID) werden nach der Eintragung ergänzt.</p>
    @endif

    <h2>Hinweis zu den Inhalten der Mieterportale</h2>
    <p>
        Die Mieterportale werden von den jeweiligen Liegenschaftsverwaltungen betrieben, die unsere Software nutzen.
        Für die Inhalte, die eine Verwaltung in ihrem Portal bereitstellt (z. B. Dokumente, Mitteilungen, Rechnungen),
        ist die jeweilige Verwaltung verantwortlich.
    </p>

    <h2>Haftungsausschluss</h2>
    <p>
        Wir prüfen die Inhalte dieser Website sorgfältig. Dennoch übernehmen wir keine Gewähr für die Richtigkeit,
        Genauigkeit, Aktualität, Zuverlässigkeit und Vollständigkeit der Informationen. Haftungsansprüche wegen Schäden
        materieller oder immaterieller Art, die aus dem Zugriff auf oder der Nutzung bzw. Nichtnutzung der
        veröffentlichten Informationen, durch Missbrauch der Verbindung oder durch technische Störungen entstanden sind,
        werden – soweit gesetzlich zulässig – ausgeschlossen.
    </p>

    <h2>Haftung für Links</h2>
    <p>
        Verweise und Links auf Websites Dritter liegen ausserhalb unseres Verantwortungsbereichs. Der Zugriff und die
        Nutzung solcher Websites erfolgen auf eigene Gefahr der Nutzerin bzw. des Nutzers.
    </p>

    <h2>Urheberrechte</h2>
    <p>
        Die Urheber- und alle anderen Rechte an Inhalten, Bildern, Software und anderen Dateien auf dieser Website
        gehören ausschliesslich der {{ config('legal.company') }} oder den speziell genannten Rechteinhabern. Für die
        Reproduktion jeglicher Elemente ist die schriftliche Zustimmung der Rechteinhaber im Voraus einzuholen.
    </p>
@endsection
