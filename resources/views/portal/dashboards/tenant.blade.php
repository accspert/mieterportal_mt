<!-- Mieter / Tenant Specialized Dashboard -->

<!-- Apartment & Lease Contract Hero Card -->
@if($primaryUnit)
<div class="card hero-unit-card mb-5">
    <div class="d-flex flex-between flex-wrap gap-5" style="align-items: flex-start;">
        <div>
            <div class="d-flex flex-center gap-2 mb-2" style="display: inline-flex; padding: 3px 8px; border-radius: 6px; background: rgba(59, 130, 246, 0.15); color: #2563eb; font-size: 12px; font-weight: 700;">
                <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="icon-xs">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                </svg>
                Ihre Mieteinheit
            </div>
            <h2 class="text-2xl mb-1 text-main">{{ $primaryUnit->property->name ?? 'Mietobjekt' }} — {{ $primaryUnit->number }}</h2>
            <div class="text-base text-muted font-500">
                {{ $primaryUnit->property->street ?? '' }}, {{ $primaryUnit->property->postal_code ?? '' }} {{ $primaryUnit->property->city ?? '' }}
            </div>
        </div>

        @if($leaseContractDoc)
            <a href="{{ route('portal.documents.download', $leaseContractDoc) }}" target="_blank" class="btn btn-primary btn-sm">
                <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="icon-sm">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                </svg>
                Mietvertrag (PDF)
            </a>
        @endif
    </div>

    <div class="grid-auto-sm pt-4 mt-4 border-top">
        <div>
            <div class="detail-label">Etage</div>
            <div class="detail-value">{{ $primaryUnit->floor ?: 'Erdgeschoss' }}</div>
        </div>
        <div>
            <div class="detail-label">Wohnfläche</div>
            <div class="detail-value">{{ $primaryUnit->area ? number_format($primaryUnit->area, 1, ',', '.') . ' m²' : '-' }}</div>
        </div>
        <div>
            <div class="detail-label">Zimmer</div>
            <div class="detail-value">{{ $primaryUnit->rooms ?: '-' }}</div>
        </div>
        <div>
            <div class="detail-label">Mietbeginn</div>
            <div class="detail-value">{{ ($tenant && $tenant->move_in) ? \Carbon\Carbon::parse($tenant->move_in)->format('d.m.Y') : 'Unbefristet' }}</div>
        </div>
    </div>
</div>
@endif

<!-- Stat Grid -->
<div class="stat-grid mb-5">
    <div class="stat-card stat-warning">
        <div class="stat-icon">
            <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="icon-lg">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v3.026a2.999 2.999 0 010 5.198v3.026c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-3.026a2.999 2.999 0 010-5.198V6.375c0-.621-.504-1.125-1.125-1.125H3.375z" />
            </svg>
        </div>
        <div class="stat-info">
            <div class="stat-value">{{ $stats['active_tickets'] ?? 0 }}</div>
            <div class="stat-label">Offene Tickets</div>
        </div>
    </div>

    <div class="stat-card stat-accent">
        <div class="stat-icon">
            <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="icon-lg">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a.75.75 0 01-1.074-.707V18.5C2.477 16.89 1.5 14.582 1.5 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z" />
            </svg>
        </div>
        <div class="stat-info">
            <div class="stat-value">{{ $stats['unread_messages'] ?? 0 }}</div>
            <div class="stat-label">Ungelesene Nachrichten</div>
        </div>
    </div>

    <div class="stat-card stat-success">
        <div class="stat-icon">
            <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="icon-lg">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
            </svg>
        </div>
        <div class="stat-info">
            <div class="stat-value">{{ $stats['documents_count'] ?? 0 }}</div>
            <div class="stat-label">Dokumente verfügbar</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon">
            <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="icon-lg">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
            </svg>
        </div>
        <div class="stat-info">
            <div class="stat-value">{{ $stats['open_invoices'] ?? 0 }}</div>
            <div class="stat-label">Offene Rechnungen</div>
        </div>
    </div>
</div>

<div class="grid-auto-lg mb-5">
    <!-- Offene Tickets / Schadensmeldungen -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <span>Meine Tickets &amp; Schadensmeldungen</span>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('portal.tickets.create') }}" class="btn btn-primary btn-xs">+ Schaden melden</a>
                <a href="{{ route('portal.tickets.index') }}" class="btn btn-outline btn-xs">Alle anzeigen</a>
            </div>
        </div>

        @if($recentTickets->isEmpty())
            <div class="empty-state">
                <div class="empty-state-icon">✨</div>
                <div class="font-600 mb-1">Keine aktiven Tickets</div>
                <div class="text-base">In Ihrer Wohnung sind derzeit keine offenen Schäden gemeldet.</div>
            </div>
        @else
            <div class="flex-col gap-3">
                @foreach($recentTickets as $ticket)
                    <a href="{{ route('portal.tickets.show', $ticket) }}" class="sub-card list-row" style="text-decoration: none; color: inherit;">
                        <div>
                            <div class="item-title">{{ $ticket->title }}</div>
                            <div class="item-subtitle">
                                Kategorie: {{ ucfirst($ticket->category) }} &bull; {{ $ticket->created_at->diffForHumans() }}
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

    <!-- Aktuelle Rechnungen & Mietzahlungen -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <span>Rechnungen &amp; Zahlungsübersicht</span>
            </div>
            <a href="{{ route('portal.invoices.index') }}" class="btn btn-outline btn-xs">Alle Rechnungen</a>
        </div>

        @if($recentInvoices->isEmpty())
            <div class="empty-state">
                <div class="empty-state-icon">💳</div>
                <div class="font-600 mb-1">Keine Rechnungen vorhanden</div>
                <div class="text-base">Aktuell liegen keine offenen Forderungen vor.</div>
            </div>
        @else
            <div class="flex-col gap-3">
                @foreach($recentInvoices as $invoice)
                    <div class="sub-card list-row">
                        <div>
                            <div class="item-title">{{ $invoice->invoice_number }}</div>
                            <div class="item-subtitle">
                                Fällig: {{ $invoice->due_date ? $invoice->due_date->format('d.m.Y') : '-' }} &bull; {{ number_format($invoice->amount, 2, ',', '.') }} €
                            </div>
                        </div>
                        <div class="d-flex flex-center gap-2">
                            <span class="badge {{ $invoice->status === 'paid' ? 'badge-paid' : 'badge-open' }}">
                                {{ $invoice->status === 'paid' ? 'Bezahlt' : 'Ausstehend' }}
                            </span>
                            <a href="{{ route('portal.invoices.download', $invoice) }}" class="btn btn-outline btn-xs">
                                <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="icon-xs" style="color: var(--color-danger-600);">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                </svg>
                                <span>PDF</span>
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

<div class="grid-auto-lg">
    <!-- Letzte Dokumente -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <span>Letzte Dokumente &amp; Unterlagen</span>
            </div>
            <a href="{{ route('portal.documents.index') }}" class="btn btn-outline btn-xs">Zum Archiv</a>
        </div>

        @if($recentDocuments->isEmpty())
            <div class="empty-state">
                <div class="empty-state-icon">📄</div>
                <div class="font-600 mb-1">Keine Dokumente hinterlegt</div>
                <div class="text-base">Sobald neue Abrechnungen oder Verträge bereitgestellt werden, erscheinen sie hier.</div>
            </div>
        @else
            <div class="flex-col gap-3">
                @foreach($recentDocuments as $doc)
                    @php $badge = $doc->file_type_badge; @endphp
                    <div class="sub-card list-row">
                        <div class="d-flex flex-center gap-3">
                            <div style="width: 34px; height: 34px; min-width: 34px; border-radius: 6px; background: {{ $badge['bg'] }}; color: {{ $badge['color'] }}; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 11px;">
                                {{ $badge['label'] }}
                            </div>
                            <div>
                                <div class="item-title text-base">{{ $doc->filename }}</div>
                                <div class="item-subtitle text-xs">
                                    {{ ucfirst($doc->category ?? 'Allgemein') }} &bull; {{ $doc->type_label }} &bull; {{ $doc->created_at->format('d.m.Y') }}
                                </div>
                            </div>
                        </div>
                        <a href="{{ route('portal.documents.download', $doc) }}" class="btn btn-outline btn-xs">
                            <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="icon-xs" style="color: var(--color-primary-600);">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                            </svg>
                            <span>Download</span>
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
            <a href="{{ route('portal.messages.index') }}" class="btn btn-outline btn-xs">Zum Chat</a>
        </div>

        <form method="POST" action="{{ route('portal.messages.store') }}">
            @csrf
            <div class="form-group">
                <label class="form-label" for="subject">Betreff</label>
                <input type="text" id="subject" name="subject" class="form-control" placeholder="z.B. Frage zur Nebenkostenabrechnung" required>
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
