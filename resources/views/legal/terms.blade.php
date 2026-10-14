@extends('legal.layout')

@section('title', 'Allgemeine Geschäftsbedingungen')

@section('legal')
    <p>
        Allgemeine Geschäftsbedingungen (AGB) der {{ config('legal.company') }}, {{ config('legal.street') }},
        {{ config('legal.postal_code') }} {{ config('legal.city') }} (nachfolgend «Anbieterin»), für die Nutzung der
        Software «Liegenschaftsportal».
    </p>

    <h2>1. Geltungsbereich</h2>
    <p>
        Diese AGB gelten für alle Verträge über die Nutzung des Liegenschaftsportals zwischen der Anbieterin und ihren
        Kundinnen und Kunden (Liegenschaftsverwaltungen, Vermieterinnen und Vermieter, Stockwerkeigentümergemeinschaften
        und weitere Unternehmen; nachfolgend «Kunde»). Das Angebot richtet sich ausschliesslich an Unternehmen und nicht
        an Konsumentinnen und Konsumenten. Abweichende Bedingungen des Kunden gelten nur, wenn die Anbieterin ihnen
        schriftlich zugestimmt hat.
    </p>

    <h2>2. Vertragsschluss</h2>
    <p>
        Der Vertrag kommt mit der Annahme eines Angebots der Anbieterin durch den Kunden oder mit der Freischaltung des
        Kundenzugangs zustande. Massgebend für Leistungsumfang und Preise ist das jeweilige Angebot bzw. die
        Auftragsbestätigung.
    </p>

    <h2>3. Leistungen der Anbieterin</h2>
    <p>
        Die Anbieterin stellt dem Kunden das Liegenschaftsportal als Software-as-a-Service über das Internet zur Verfügung.
        Der Kunde erhält einen eigenen, von anderen Kunden technisch getrennten Bereich (eigene Datenbank und eigener
        Dateispeicher) mit Zugang für seine Mitarbeitenden sowie seine Mieterinnen, Mieter und Eigentümer.
        Die Anbieterin entwickelt die Software laufend weiter und darf Funktionen ändern, sofern der vertraglich
        vereinbarte Kernumfang erhalten bleibt.
    </p>

    <h2>4. Verfügbarkeit und Wartung</h2>
    <p>
        Die Anbieterin bemüht sich um eine möglichst unterbruchsfreie Verfügbarkeit des Liegenschaftsportals. Ausgenommen sind
        angekündigte Wartungsarbeiten, die nach Möglichkeit ausserhalb der üblichen Geschäftszeiten erfolgen, sowie
        Unterbrüche, die ausserhalb des Einflussbereichs der Anbieterin liegen (z. B. Störungen des Internets, höhere
        Gewalt). Eine bestimmte Verfügbarkeit ist nur geschuldet, wenn sie im Angebot ausdrücklich vereinbart ist.
    </p>

    <h2>5. Pflichten des Kunden</h2>
    <ul>
        <li>Der Kunde hält Zugangsdaten geheim, schützt sie vor dem Zugriff Dritter und informiert die Anbieterin unverzüglich bei Verdacht auf Missbrauch.</li>
        <li>Der Kunde ist für die von ihm und seinen Nutzenden erfassten Inhalte verantwortlich und stellt sicher, dass er zu deren Bearbeitung berechtigt ist, insbesondere nach dem Datenschutzrecht.</li>
        <li>Der Kunde legt Benutzerkonten nur für berechtigte Personen an und entzieht Zugänge, sobald die Berechtigung entfällt.</li>
        <li>Das Liegenschaftsportal darf nicht missbräuchlich genutzt werden, insbesondere nicht für rechtswidrige Inhalte oder Angriffe auf die Systeme der Anbieterin oder Dritter.</li>
    </ul>

    <h2>6. Daten des Kunden und Datenschutz</h2>
    <p>
        Die Daten, die der Kunde im Liegenschaftsportal bearbeitet, bleiben Eigentum des Kunden. Der Kunde ist dafür
        datenschutzrechtlich verantwortlich; die Anbieterin bearbeitet sie ausschliesslich als Auftragsbearbeiterin nach
        den Weisungen des Kunden. Die Einzelheiten regelt ein Auftragsbearbeitungsvertrag (AVV), der Bestandteil des
        Vertrags ist. Die Anbieterin trifft angemessene technische und organisatorische Massnahmen zum Schutz der Daten
        und erstellt regelmässig Datensicherungen. Im Übrigen gilt die
        <a href="{{ route('legal.privacy') }}">Datenschutzerklärung</a>.
    </p>

    <h2>7. Vergütung und Zahlungsbedingungen</h2>
    <p>
        Die Vergütung richtet sich nach dem vereinbarten Abonnement. Sofern nichts anderes vereinbart ist, werden die
        Gebühren im Voraus für die jeweilige Abrechnungsperiode in Rechnung gestellt und sind innert 30 Tagen zahlbar.
        Alle Preise verstehen sich in Schweizer Franken zuzüglich der gesetzlichen Mehrwertsteuer, soweit diese anfällt.
        Bei Zahlungsverzug kann die Anbieterin den Zugang nach vorgängiger schriftlicher Mahnung mit einer Frist von
        mindestens 10 Tagen sperren; die Pflicht zur Zahlung bleibt bestehen.
    </p>

    <h2>8. Laufzeit und Kündigung</h2>
    <p>
        Sofern im Angebot nichts anderes vereinbart ist, wird der Vertrag auf unbestimmte Zeit abgeschlossen und kann von
        beiden Parteien mit einer Frist von drei Monaten auf das Ende einer Abrechnungsperiode schriftlich gekündigt
        werden. Das Recht zur fristlosen Kündigung aus wichtigem Grund bleibt vorbehalten.
    </p>

    <h2>9. Datenherausgabe bei Vertragsende</h2>
    <p>
        Bis 30 Tage nach Vertragsende kann der Kunde die Herausgabe seiner Daten in einem gängigen elektronischen Format
        verlangen. Danach löscht die Anbieterin die Daten des Kunden einschliesslich seiner Datenbank und Dateien;
        Datensicherungen werden nach Ablauf ihrer Aufbewahrungsfrist überschrieben. Gesetzliche Aufbewahrungspflichten
        bleiben vorbehalten.
    </p>

    <h2>10. Gewährleistung und Haftung</h2>
    <p>
        Die Anbieterin erbringt ihre Leistungen sorgfältig und nach dem anerkannten Stand der Technik. Sie haftet für
        Schäden, die sie absichtlich oder grobfahrlässig verursacht hat. Die Haftung für leichte Fahrlässigkeit, für
        indirekte Schäden und Folgeschäden, entgangenen Gewinn sowie für Datenverluste, soweit diese bei
        ordnungsgemässer Datensicherung vermeidbar gewesen wären, ist – soweit gesetzlich zulässig – ausgeschlossen.
        Die Haftung für Hilfspersonen ist im gesetzlich zulässigen Umfang ausgeschlossen. Zwingende gesetzliche
        Haftungsbestimmungen bleiben vorbehalten.
    </p>

    <h2>11. Vertraulichkeit</h2>
    <p>
        Die Parteien behandeln alle vertraulichen Informationen, die ihnen im Rahmen der Zusammenarbeit bekannt werden,
        vertraulich. Diese Pflicht gilt auch nach Vertragsende fort.
    </p>

    <h2>12. Änderungen dieser AGB</h2>
    <p>
        Die Anbieterin kann diese AGB anpassen. Änderungen werden dem Kunden mindestens 30 Tage vor Inkrafttreten
        schriftlich oder per E-Mail mitgeteilt. Widerspricht der Kunde nicht innert dieser Frist, gelten die Änderungen
        als genehmigt. Im Fall eines Widerspruchs kann jede Partei den Vertrag auf das Inkrafttreten der Änderung kündigen.
    </p>

    <h2>13. Schlussbestimmungen</h2>
    <p>
        Sollte eine Bestimmung dieser AGB unwirksam sein, bleibt die Gültigkeit der übrigen Bestimmungen unberührt.
        Es gilt ausschliesslich schweizerisches Recht unter Ausschluss des Übereinkommens der Vereinten Nationen über
        Verträge über den internationalen Warenkauf (Wiener Kaufrecht). Ausschliesslicher Gerichtsstand ist
        {{ config('legal.jurisdiction') }}.
    </p>
@endsection
