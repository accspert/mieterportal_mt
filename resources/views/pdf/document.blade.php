<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>{{ $document->filename }}</title>
    <style>
        @page {
            margin: 25mm 20mm;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 13px;
            color: #1e293b;
            line-height: 1.6;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #0284c7;
            padding-bottom: 15px;
            margin-bottom: 30px;
        }
        .logo {
            font-size: 22px;
            font-weight: bold;
            color: #0284c7;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .sublogo {
            font-size: 11px;
            color: #64748b;
        }
        .doc-meta {
            text-align: right;
            font-size: 11px;
            color: #64748b;
        }
        .title {
            font-size: 20px;
            font-weight: bold;
            color: #0f172a;
            margin-bottom: 8px;
        }
        .badge {
            display: inline-block;
            padding: 4px 10px;
            background: #e0f2fe;
            color: #0369a1;
            font-size: 11px;
            font-weight: 600;
            border-radius: 4px;
            text-transform: uppercase;
            margin-bottom: 20px;
        }
        .info-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 16px;
            margin-bottom: 25px;
        }
        .info-grid {
            width: 100%;
        }
        .info-label {
            font-size: 11px;
            color: #64748b;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 4px;
        }
        .info-value {
            font-size: 13px;
            font-weight: 600;
            color: #1e293b;
        }
        .content-box {
            padding: 20px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            min-height: 250px;
            margin-bottom: 40px;
        }
        .footer-table {
            width: 100%;
            border-top: 1px solid #cbd5e1;
            padding-top: 15px;
            font-size: 10px;
            color: #94a3b8;
        }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td>
                <div class="logo">Mieterportal</div>
                <div class="sublogo">Digitale Immobilien- & Hausverwaltung</div>
            </td>
            <td class="doc-meta">
                <div><strong>Dokument-ID:</strong> #DOC-{{ str_pad($document->id, 5, '0', STR_PAD_LEFT) }}</div>
                <div><strong>Datum:</strong> {{ $document->created_at->format('d.m.Y') }}</div>
                <div><strong>Version:</strong> {{ $document->version ?? '1.0' }}</div>
            </td>
        </tr>
    </table>

    <div class="title">{{ $document->filename }}</div>
    <div class="badge">Kategorie: {{ ucfirst($document->category ?? 'Allgemein') }}</div>

    <div class="info-box">
        <table class="info-grid">
            <tr>
                <td style="width: 50%; vertical-align: top;">
                    <div class="info-label">Bereitgestellt für</div>
                    <div class="info-value">
                        @if(($document->documentable_type === \App\Models\Renter::class || $document->documentable_type === \App\Models\Tenant::class) && $document->documentable)
                            Mieter: {{ $document->documentable->first_name }} {{ $document->documentable->last_name }}
                        @elseif($document->documentable_type === \App\Models\Property::class && $document->documentable)
                            Liegenschaft: {{ $document->documentable->name }}
                        @elseif($document->documentable_type === \App\Models\Unit::class && $document->documentable)
                            Einheit: {{ $document->documentable->number }} ({{ $document->documentable->property->name ?? '' }})
                        @elseif($document->documentable_type === \App\Models\Owner::class && $document->documentable)
                            Eigentümer: {{ $document->documentable->first_name }} {{ $document->documentable->last_name }}
                        @else
                            Mieterportal Nutzer
                        @endif
                    </div>
                </td>
                <td style="width: 50%; vertical-align: top;">
                    <div class="info-label">Dokumenten-Status</div>
                    <div class="info-value">Gültig & Digital archiviert</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="content-box">
        @if($document->category === 'contract' || str_contains(strtolower($document->filename), 'mietvertrag'))
            <h3 style="margin-top: 0; color: #0f172a; font-size: 16px; border-bottom: 1px solid #e2e8f0; padding-bottom: 8px;">Wohnraum-Mietvertrag / Vertragsbestätigung</h3>
            
            @php
                $activeRenter = ($document->documentable instanceof \App\Models\Renter || $document->documentable instanceof \App\Models\Tenant) 
                    ? $document->documentable 
                    : ($tenant ?? null);
                $unit = $activeRenter?->units?->first();
                $property = $unit?->property;
            @endphp

            <table style="width: 100%; margin-top: 15px; margin-bottom: 20px; border-collapse: collapse;">
                <tr style="border-bottom: 1px solid #f1f5f9;">
                    <td style="padding: 6px 0; color: #64748b; width: 35%;"><strong>Mieter:</strong></td>
                    <td style="padding: 6px 0; color: #0f172a;">{{ $activeRenter ? $activeRenter->first_name . ' ' . $activeRenter->last_name : 'Mieter' }}</td>
                </tr>
                @if($property)
                <tr style="border-bottom: 1px solid #f1f5f9;">
                    <td style="padding: 6px 0; color: #64748b;"><strong>Mietobjekt / Liegenschaft:</strong></td>
                    <td style="padding: 6px 0; color: #0f172a;">{{ $property->name }} ({{ $property->street }}, {{ $property->postal_code }} {{ $property->city }})</td>
                </tr>
                @endif
                @if($unit)
                <tr style="border-bottom: 1px solid #f1f5f9;">
                    <td style="padding: 6px 0; color: #64748b;"><strong>Wohnungsnummer / Lage:</strong></td>
                    <td style="padding: 6px 0; color: #0f172a;">Top {{ $unit->number }} — Etage: {{ $unit->floor ?: 'EG' }}</td>
                </tr>
                <tr style="border-bottom: 1px solid #f1f5f9;">
                    <td style="padding: 6px 0; color: #64748b;"><strong>Wohnfläche / Zimmer:</strong></td>
                    <td style="padding: 6px 0; color: #0f172a;">{{ $unit->area ? number_format($unit->area, 1, ',', '.') . ' m²' : '-' }} | {{ $unit->rooms ? $unit->rooms . ' Zimmer' : '-' }}</td>
                </tr>
                <tr style="border-bottom: 1px solid #f1f5f9;">
                    <td style="padding: 6px 0; color: #64748b;"><strong>Mietbeginn:</strong></td>
                    <td style="padding: 6px 0; color: #0f172a;">{{ $activeRenter && $activeRenter->move_in ? \Carbon\Carbon::parse($activeRenter->move_in)->format('d.m.Y') : 'Unbefristetes Mietverhältnis' }}</td>
                </tr>
                <tr style="border-bottom: 1px solid #f1f5f9;">
                    <td style="padding: 6px 0; color: #64748b;"><strong>Monatlicher Mietzins:</strong></td>
                    <td style="padding: 6px 0; color: #0f172a; font-weight: bold;">{{ $unit->rent ? number_format($unit->rent, 2, ',', '.') . ' CHF / EUR' : 'Gemäß Zahlungsvereinbarung' }}</td>
                </tr>
                @endif
            </table>

            <h4 style="margin-top: 20px; margin-bottom: 6px; font-size: 13px; color: #1e293b;">Vertragsbestimmungen & Gültigkeit</h4>
            <p style="font-size: 12px; color: #475569; margin: 4px 0;">
                1. Das Mietverhältnis unterliegt den gesetzlichen Bestimmungen des Mietrechts sowie der Hausordnung der Liegenschaft.
            </p>
            <p style="font-size: 12px; color: #475569; margin: 4px 0;">
                2. Die Mietzinszahlungen sowie Nebenkostenvorauszahlungen sind jeweils zum Monatsersten im Voraus fällig.
            </p>
            <p style="font-size: 12px; color: #475569; margin: 4px 0;">
                3. Dieses Dokument dokumentiert den aktiven Mietvertrag im digitalen Mieterportal und ist ohne handschriftliche Unterschrift rechtsverbindlich hinterlegt.
            </p>
        @else
            <h3 style="margin-top: 0; color: #0f172a; font-size: 15px;">Dokumentenauszug / Inhalt</h3>
            <p>Dieses Dokument wurde im Rahmen der digitalen Liegenschafts- und Mietverwaltung digital bereitgestellt.</p>
            <p><strong>Dateiname:</strong> {{ $document->filename }}</p>
            <p><strong>Kategorie:</strong> {{ match($document->category) {
                'contract' => 'Vertragsdokumentation / Mietvertrag',
                'invoice' => 'Abrechnungsbeleg / Betriebskosten',
                'protocol' => 'Übergabeprotokoll / Begehung',
                default => 'Allgemeine Unterlage / Hausordnung'
            } }}</p>
            <p style="margin-top: 30px; font-size: 12px; color: #64748b;">
                Dieses Dokument ist digital signiert und im Mieterportal rechtssicher archiviert. Bei Fragen zu diesem Dokument wenden Sie sich bitte über das Portal direkt an Ihren zuständigen Sachbearbeiter.
            </p>
        @endif
    </div>

    <table class="footer-table">
        <tr>
            <td>
                Mieterportal Immobilienverwaltung &bull; Vertrauliches Dokument
            </td>
            <td style="text-align: right;">
                Erstellt am {{ now()->format('d.m.Y H:i') }} Uhr
            </td>
        </tr>
    </table>
</body>
</html>
