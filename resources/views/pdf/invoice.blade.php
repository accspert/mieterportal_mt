<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Rechnung {{ $invoice->invoice_number }}</title>
    <style>
        @page {
            margin: 20mm 20mm 20mm 20mm;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 13px;
            color: #1e293b;
            line-height: 1.4;
        }
        .header {
            width: 100%;
            margin-bottom: 30px;
        }
        .company-logo {
            font-size: 24px;
            font-weight: bold;
            color: #2563eb;
            letter-spacing: -0.5px;
        }
        .company-sub {
            font-size: 11px;
            color: #64748b;
            text-transform: uppercase;
        }
        .meta-table {
            width: 100%;
            margin-bottom: 35px;
        }
        .meta-table td {
            vertical-align: top;
        }
        .recipient-box {
            width: 55%;
            font-size: 13px;
            line-height: 1.5;
        }
        .invoice-details {
            width: 45%;
            text-align: right;
            font-size: 12px;
        }
        .invoice-details strong {
            color: #0f172a;
        }
        .title-section {
            margin-bottom: 25px;
            border-bottom: 2px solid #2563eb;
            padding-bottom: 10px;
        }
        .title-section h1 {
            font-size: 22px;
            margin: 0 0 4px 0;
            color: #0f172a;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .items-table th {
            background-color: #f1f5f9;
            color: #475569;
            text-align: left;
            padding: 10px;
            font-size: 11px;
            text-transform: uppercase;
            border-bottom: 1px solid #cbd5e1;
        }
        .items-table td {
            padding: 12px 10px;
            border-bottom: 1px solid #e2e8f0;
        }
        .text-right {
            text-align: right;
        }
        .totals-section {
            width: 100%;
            margin-bottom: 40px;
        }
        .totals-table {
            width: 40%;
            margin-left: auto;
            border-collapse: collapse;
        }
        .totals-table td {
            padding: 6px 10px;
        }
        .totals-table tr.grand-total td {
            border-top: 2px solid #0f172a;
            border-bottom: 2px solid #0f172a;
            font-size: 15px;
            font-weight: bold;
            color: #0f172a;
            padding-top: 10px;
            padding-bottom: 10px;
        }
        .notes-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 14px;
            margin-bottom: 30px;
            font-size: 12px;
            color: #475569;
        }
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            font-size: 10px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 10px;
            text-align: center;
        }
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
        }
        .badge-paid { background: #dcfce7; color: #15803d; }
        .badge-sent { background: #dbeafe; color: #1d4ed8; }
        .badge-overdue { background: #fee2e2; color: #b91c1c; }
        .badge-draft { background: #f1f5f9; color: #475569; }
    </style>
</head>
<body>
    <table class="header">
        <tr>
            <td>
                <div class="company-logo">Mieterportal Immobilien GmbH</div>
                <div class="company-sub">Professionelle Haus- & Liegenschaftsverwaltung</div>
            </td>
            <td class="text-right" style="font-size: 11px; color: #64748b;">
                Musterstraße 42<br>
                20095 Hamburg<br>
                Tel: +49 (0) 40 123 456 78<br>
                verwaltung@mieterportal.local
            </td>
        </tr>
    </table>

    <table class="meta-table">
        <tr>
            <td class="recipient-box">
                <span style="font-size: 10px; color: #94a3b8; text-decoration: underline;">Mieterportal GmbH &bull; Musterstraße 42 &bull; 20095 Hamburg</span><br><br>
                @if($invoice->tenant)
                    <strong>{{ $invoice->tenant->first_name }} {{ $invoice->tenant->last_name }}</strong><br>
                    @if($invoice->unit && $invoice->unit->property)
                        {{ $invoice->unit->property->street }} (Einheit {{ $invoice->unit->number }})<br>
                        {{ $invoice->unit->property->postal_code }} {{ $invoice->unit->property->city }}
                    @endif
                @elseif($invoice->owner)
                    <strong>{{ $invoice->owner->company_name ?: ($invoice->owner->first_name . ' ' . $invoice->owner->last_name) }}</strong><br>
                    {{ $invoice->owner->street }}<br>
                    {{ $invoice->owner->postal_code }} {{ $invoice->owner->city }}
                @else
                    <strong>Empfänger</strong>
                @endif
            </td>
            <td class="invoice-details">
                <p>
                    <strong>Rechnungsnummer:</strong> {{ $invoice->invoice_number }}<br>
                    <strong>Rechnungsdatum:</strong> {{ $invoice->created_at ? $invoice->created_at->format('d.m.Y') : date('d.m.Y') }}<br>
                    <strong>Fälligkeitsdatum:</strong> <span style="color: #2563eb; font-weight: bold;">{{ $invoice->due_date ? date('d.m.Y', strtotime($invoice->due_date)) : '-' }}</span><br>
                    <strong>Status:</strong> 
                    <span class="badge badge-{{ $invoice->status }}">
                        {{ match($invoice->status) {
                            'paid' => 'Bezahlt',
                            'sent' => 'Offen',
                            'draft' => 'Entwurf',
                            'overdue' => 'Überfällig',
                            'cancelled' => 'Storniert',
                            default => $invoice->status
                        } }}
                    </span>
                </p>
            </td>
        </tr>
    </table>

    <div class="title-section">
        <h1>Rechnung / Abrechnungsbeleg</h1>
        <div style="font-size: 13px; color: #64748b;">
            @if($invoice->unit)
                Objekt: {{ $invoice->unit->property->name ?? 'Liegenschaft' }} &bull; Einheit: {{ $invoice->unit->number }} ({{ $invoice->unit->floor ?? 'EG' }})
            @endif
        </div>
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 10%;">Pos.</th>
                <th style="width: 60%;">Bezeichnung / Leistungsbeschreibung</th>
                <th class="text-right" style="width: 30%;">Betrag</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>1</td>
                <td>
                    <strong>Miete / Nebenkosten / Hausgeld</strong><br>
                    <span style="font-size: 11px; color: #64748b;">
                        {{ $invoice->notes ?: 'Monatliche Pauschale bzw. Abrechnungsbetrag gemäß Mietvertrag' }}
                    </span>
                </td>
                <td class="text-right">
                    <strong>{{ number_format($invoice->amount, 2, ',', '.') }} €</strong>
                </td>
            </tr>
        </tbody>
    </table>

    <div class="totals-section">
        <table class="totals-table">
            <tr>
                <td style="color: #64748b;">Nettobetrag:</td>
                <td class="text-right">{{ number_format($invoice->amount / 1.19, 2, ',', '.') }} €</td>
            </tr>
            <tr>
                <td style="color: #64748b;">MwSt. (19%):</td>
                <td class="text-right">{{ number_format($invoice->amount - ($invoice->amount / 1.19), 2, ',', '.') }} €</td>
            </tr>
            <tr class="grand-total">
                <td>Gesamtbetrag:</td>
                <td class="text-right">{{ number_format($invoice->amount, 2, ',', '.') }} €</td>
            </tr>
        </table>
    </div>

    <div class="notes-box">
        <strong>Zahlungshinweis:</strong><br>
        Bitte überweisen Sie den Gesamtbetrag bis zum <strong>{{ $invoice->due_date ? date('d.m.Y', strtotime($invoice->due_date)) : '-' }}</strong> unter Angabe der Rechnungsnummer <strong>{{ $invoice->invoice_number }}</strong> auf unser nachfolgendes Geschäftskonto. Bei bestehendem SEPA-Lastschriftmandat wird der Betrag automatisch eingezogen.
    </div>

    <div class="footer">
        Mieterportal Immobilien GmbH &bull; Amtsgericht Hamburg HRB 123456 &bull; USt-ID: DE987654321<br>
        Bankverbindung: Hamburger Sparkasse &bull; IBAN: DE89 2005 0550 1234 5678 90 &bull; BIC: HASPDEHHXXX
    </div>
</body>
</html>
