<!-- Eigentümer / Owner Specialized Dashboard -->

<!-- Stat Grid for Property Portfolio -->
<div class="stat-grid" style="margin-bottom: 24px;">
    <div class="stat-card stat-accent">
        <div class="stat-icon">
            <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:24px;height:24px;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008z" />
            </svg>
        </div>
        <div class="stat-info">
            <div class="stat-value">{{ $stats['total_properties'] }}</div>
            <div class="stat-label">Liegenschaften</div>
        </div>
    </div>

    <div class="stat-card stat-success">
        <div class="stat-icon">
            <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:24px;height:24px;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 21v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21m0 0h4.5V3.75a.75.75 0 00-.75-.75h-9a.75.75 0 00-.75.75V21h4.5z" />
            </svg>
        </div>
        <div class="stat-info">
            <div class="stat-value">{{ $stats['occupied_units'] }} / {{ $stats['total_units'] }}</div>
            <div class="stat-label">Vermietungsquote ({{ $stats['occupancy_rate'] }}%)</div>
        </div>
    </div>

    <div class="stat-card stat-warning">
        <div class="stat-icon">
            <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:24px;height:24px;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v3.026a2.999 2.999 0 010 5.198v3.026c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-3.026a2.999 2.999 0 010-5.198V6.375c0-.621-.504-1.125-1.125-1.125H3.375z" />
            </svg>
        </div>
        <div class="stat-info">
            <div class="stat-value">{{ $stats['active_tickets'] }}</div>
            <div class="stat-label">Offene Meldungen</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon">
            <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:24px;height:24px;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
            </svg>
        </div>
        <div class="stat-info">
            <div class="stat-value">{{ $stats['open_invoices'] }}</div>
            <div class="stat-label">Abrechnungen / Belege</div>
        </div>
    </div>
</div>

<!-- Immobilienübersicht (Portfolio Grid) -->
<div class="card" style="margin-bottom: 24px;">
    <div class="card-header">
        <div class="card-title">
            <span>Meine Liegenschaften & Einheiten</span>
        </div>
        <a href="{{ route('portal.units.index') }}" class="btn btn-outline" style="font-size: 12px; padding: 5px 10px;">Alle Einheiten anzeigen</a>
    </div>

    @if($properties->isEmpty())
        <div style="text-align: center; padding: 32px 16px; color: var(--text-muted);">
            <div style="font-size: 32px; margin-bottom: 8px;">🏢</div>
            <div style="font-weight: 600; margin-bottom: 4px;">Keine Liegenschaften zugewiesen</div>
            <div style="font-size: 13px;">Wenden Sie sich an Ihre Hausverwaltung zur Freischaltung Ihres Portfolios.</div>
        </div>
    @else
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 16px;">
            @foreach($properties as $property)
                @php
                    $unitCount = $property->units->count();
                    $occupied = $property->units->whereNotNull('current_tenant_id')->count();
                    $rate = $unitCount > 0 ? round(($occupied / $unitCount) * 100) : 0;
                @endphp
                <div style="padding: 16px; background: rgba(0,0,0,0.25); border: 1px solid var(--border-color); border-radius: var(--radius-md); display: flex; flex-direction: column; justify-content: space-between;">
                    <div>
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 6px;">
                            <h3 style="font-size: 16px; font-weight: 700; color: #fff;">{{ $property->name }}</h3>
                            <span class="badge" style="background: rgba(37, 99, 235, 0.2); color: #60a5fa;">
                                {{ $unitCount }} {{ $unitCount === 1 ? 'Einheit' : 'Einheiten' }}
                            </span>
                        </div>
                        <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 14px;">
                            {{ $property->street }}, {{ $property->postal_code }} {{ $property->city }}
                        </div>
                    </div>

                    <div style="border-top: 1px solid rgba(255,255,255,0.06); padding-top: 10px; display: flex; justify-content: space-between; align-items: center;">
                        <div style="font-size: 12px; color: var(--text-muted);">
                            Vermietet: <strong style="color: #fff;">{{ $occupied }} / {{ $unitCount }}</strong> ({{ $rate }}%)
                        </div>
                        <div style="width: 80px; height: 6px; background: rgba(255,255,255,0.1); border-radius: 3px; overflow: hidden;">
                            <div style="height: 100%; width: {{ $rate }}%; background: #16a34a; border-radius: 3px;"></div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(360px, 1fr)); gap: 24px; margin-bottom: 24px;">
    <!-- Offene Meldungen aller Objekte -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <span>Aktuelle Meldungen aller Liegenschaften</span>
            </div>
            <a href="{{ route('portal.tickets.index') }}" class="btn btn-outline" style="font-size: 12px; padding: 5px 10px;">Alle Vorgänge</a>
        </div>

        @if($recentTickets->isEmpty())
            <div style="text-align: center; padding: 32px 16px; color: var(--text-muted);">
                <div style="font-size: 32px; margin-bottom: 8px;">✨</div>
                <div style="font-weight: 600; margin-bottom: 4px;">Keine offenen Meldungen</div>
                <div style="font-size: 13px;">In Ihren Liegenschaften liegen aktuell keine Schadensberichte vor.</div>
            </div>
        @else
            <div style="display: flex; flex-direction: column; gap: 10px;">
                @foreach($recentTickets as $ticket)
                    <a href="{{ route('portal.tickets.show', $ticket) }}" style="display: flex; align-items: center; justify-content: space-between; padding: 12px 14px; background: rgba(0,0,0,0.2); border: 1px solid var(--border-color); border-radius: var(--radius-md); text-decoration: none; color: inherit;">
                        <div>
                            <div style="font-weight: 600; font-size: 14px; margin-bottom: 2px;">{{ $ticket->title }}</div>
                            <div style="font-size: 12px; color: var(--text-muted);">
                                {{ $ticket->property->name ?? 'Objekt' }} &bull; Mieter: {{ $ticket->tenant ? "{$ticket->tenant->first_name} {$ticket->tenant->last_name}" : 'Allgemein' }}
                            </div>
                        </div>
                        <span class="badge badge-{{ $ticket->status }}">
                            {{ match($ticket->status) {
                                'open' => 'Offen',
                                'in_progress' => 'In Bearbeitung',
                                'waiting' => 'Wartet',
                                'done', 'resolved' => 'Gelöst',
                                'closed' => 'Geschlossen',
                                default => $ticket->status
                            } }}
                        </span>
                    </a>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Nebenkosten, Abrechnungen & Ausschüttungen -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <span>Abrechnungen & Ausschüttungen</span>
            </div>
            <a href="{{ route('portal.invoices.index') }}" class="btn btn-outline" style="font-size: 12px; padding: 5px 10px;">Alle Belege</a>
        </div>

        @if($recentInvoices->isEmpty())
            <div style="text-align: center; padding: 32px 16px; color: var(--text-muted);">
                <div style="font-size: 32px; margin-bottom: 8px;">📊</div>
                <div style="font-weight: 600; margin-bottom: 4px;">Keine Abrechnungen vorhanden</div>
                <div style="font-size: 13px;">Sobald die Hausverwaltung Abrechnungen verbucht, erscheinen sie hier.</div>
            </div>
        @else
            <div style="display: flex; flex-direction: column; gap: 10px;">
                @foreach($recentInvoices as $invoice)
                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px 14px; background: rgba(0,0,0,0.2); border: 1px solid var(--border-color); border-radius: var(--radius-md);">
                        <div>
                            <div style="font-weight: 600; font-size: 14px; margin-bottom: 2px;">{{ $invoice->invoice_number }}</div>
                            <div style="font-size: 12px; color: var(--text-muted);">
                                Datum: {{ $invoice->issue_date ? $invoice->issue_date->format('d.m.Y') : '-' }} &bull; {{ number_format($invoice->amount, 2, ',', '.') }} €
                            </div>
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span class="badge" style="background: {{ $invoice->status === 'paid' ? 'rgba(22, 163, 74, 0.2)' : 'rgba(217, 119, 6, 0.2)' }}; color: {{ $invoice->status === 'paid' ? '#4ade80' : '#f59e0b' }};">
                                {{ $invoice->status === 'paid' ? 'Abgerechnet' : 'In Prüfung' }}
                            </span>
                            <a href="{{ route('portal.invoices.download', $invoice) }}" class="btn btn-secondary" style="font-size: 11px; padding: 4px 8px;">
                                PDF Beleg
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(360px, 1fr)); gap: 24px;">
    <!-- Letzte Liegenschafts-Dokumente -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <span>Liegenschaftsunterlagen & Verträge</span>
            </div>
            <a href="{{ route('portal.documents.index') }}" class="btn btn-outline" style="font-size: 12px; padding: 5px 10px;">Zum Dokumentenarchiv</a>
        </div>

        @if($recentDocuments->isEmpty())
            <div style="text-align: center; padding: 32px 16px; color: var(--text-muted);">
                <div style="font-size: 32px; margin-bottom: 8px;">📁</div>
                <div style="font-weight: 600; margin-bottom: 4px;">Keine Dokumente abgelegt</div>
                <div style="font-size: 13px;">Energieausweise, Versicherungsunterlagen und Protokolle finden Sie hier.</div>
            </div>
        @else
            <div style="display: flex; flex-direction: column; gap: 10px;">
                @foreach($recentDocuments as $doc)
                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px 14px; background: rgba(0,0,0,0.2); border: 1px solid var(--border-color); border-radius: var(--radius-md);">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <div style="width: 32px; height: 32px; border-radius: 6px; background: rgba(16, 185, 129, 0.15); color: #34d399; display: flex; align-items: center; justify-content: center;">
                                <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:16px;height:16px;">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                </svg>
                            </div>
                            <div>
                                <div style="font-weight: 600; font-size: 13px;">{{ $doc->filename }}</div>
                                <div style="font-size: 11px; color: var(--text-muted);">
                                    {{ ucfirst($doc->category ?? 'Objektunterlage') }} &bull; {{ $doc->created_at->format('d.m.Y') }}
                                </div>
                            </div>
                        </div>
                        <a href="{{ route('portal.documents.download', $doc) }}" class="btn btn-secondary" style="font-size: 11px; padding: 4px 8px;">
                            Download
                        </a>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Schnelle Nachricht an Hausverwaltung -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <span>Direktkontakt zur Hausverwaltung</span>
            </div>
            <a href="{{ route('portal.messages.index') }}" class="btn btn-outline" style="font-size: 12px; padding: 5px 10px;">Zum Chat</a>
        </div>

        <form method="POST" action="{{ route('portal.messages.store') }}">
            @csrf
            <div class="form-group">
                <label class="form-label" for="subject">Betreff</label>
                <input type="text" id="subject" name="subject" class="form-control" placeholder="z.B. Rückfrage zur Abrechnung" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="body">Ihre Nachricht</label>
                <textarea id="body" name="body" class="form-textarea" rows="3" placeholder="Schreiben Sie Ihr Anliegen hier..." required></textarea>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%;">
                <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" />
                </svg>
                Nachricht absenden
            </button>
        </form>
    </div>
</div>
