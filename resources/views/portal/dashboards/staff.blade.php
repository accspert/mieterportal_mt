<!-- Quick Action Bar for Sachbearbeiter -->
<div class="card" style="margin-bottom: 24px; padding: 18px 24px;">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
        <div>
            <h2 style="font-size: 18px; font-weight: 700; color: var(--text-main); margin-bottom: 4px;">Sachbearbeiter-Zentrale</h2>
            <p style="font-size: 13px; color: var(--text-muted); font-weight: 500; margin: 0;">Schnellzugriff auf Mieterakten, Dokumenten-Uploads und Rechnungsverwaltung.</p>
        </div>
        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <a href="{{ url('/admin/tenants') }}" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 6px; font-size: 13px; padding: 8px 14px;">
                <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 16px; height: 16px;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                </svg>
                Mieter verwalten & Dokumente zuordnen
            </a>
            <a href="{{ url('/admin/documents/create') }}" class="btn btn-outline" style="display: inline-flex; align-items: center; gap: 6px; font-size: 13px; padding: 8px 14px;">
                <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 16px; height: 16px;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                </svg>
                + Dokument hochladen
            </a>
            <a href="{{ url('/admin/invoices/create') }}" class="btn btn-outline" style="display: inline-flex; align-items: center; gap: 6px; font-size: 13px; padding: 8px 14px;">
                <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 16px; height: 16px;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
                </svg>
                + Rechnung anlegen
            </a>
            <a href="{{ url('/admin') }}" class="btn btn-secondary" style="font-size: 13px; padding: 8px 14px;">
                Admin-Panel ↗
            </a>
        </div>
    </div>
</div>

<!-- Stat Grid for Staff / Sachbearbeiter -->
<div class="stat-grid" style="margin-bottom: 24px;">
    <div class="stat-card stat-primary">
        <div class="stat-icon">
            <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:24px;height:24px;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.75c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008z" />
            </svg>
        </div>
        <div class="stat-info">
            <div class="stat-value">{{ $stats['occupied_units'] ?? 0 }} / {{ $stats['total_units'] ?? 0 }}</div>
            <div class="stat-label">Vermietungsquote ({{ $stats['occupancy_rate'] ?? 0 }}%)</div>
        </div>
    </div>

    <div class="stat-card stat-warning">
        <div class="stat-icon">
            <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:24px;height:24px;">
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
            <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:24px;height:24px;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a.75.75 0 01-1.074-.707V18.5C2.477 16.89 1.5 14.582 1.5 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z" />
            </svg>
        </div>
        <div class="stat-info">
            <div class="stat-value">{{ $stats['unread_messages'] ?? 0 }}</div>
            <div class="stat-label">Ungelesene Nachrichten</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon">
            <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:24px;height:24px;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
            </svg>
        </div>
        <div class="stat-info">
            <div class="stat-value">{{ $stats['open_invoices'] ?? 0 }}</div>
            <div class="stat-label">Offene Rechnungen</div>
        </div>
    </div>
</div>

<!-- Liegenschaften & Einheiten -->
<div class="card" style="margin-bottom: 24px;">
    <div class="card-header">
        <div class="card-title">
            <span>Verwaltete Liegenschaften & Einheiten</span>
        </div>
        <a href="{{ route('portal.units.index') }}" class="btn btn-outline" style="font-size: 12px; padding: 5px 10px;">Alle Einheiten anzeigen</a>
    </div>

    @if($properties->isEmpty())
        <div style="text-align: center; padding: 32px 16px; color: var(--text-muted);">
            <div style="font-size: 32px; margin-bottom: 8px;">🏢</div>
            <div style="font-weight: 600; margin-bottom: 4px;">Keine Liegenschaften vorhanden</div>
            <div style="font-size: 13px;">Legen Sie Liegenschaften im System an, um diese hier zu verwalten.</div>
        </div>
    @else
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 16px;">
            @foreach($properties as $property)
                @php
                    $unitCount = $property->units->count();
                    $occupied = $property->units->filter(fn($u) => $u->current_renter_id || $u->current_tenant_id)->count();
                    $rate = $unitCount > 0 ? round(($occupied / $unitCount) * 100) : 0;
                @endphp
                <div class="sub-card" style="padding: 16px; display: flex; flex-direction: column; justify-content: space-between;">
                    <div>
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 6px;">
                            <h3 style="font-size: 16px; font-weight: 700; color: var(--text-main);">{{ $property->name }}</h3>
                            <span class="badge" style="background: rgba(37, 99, 235, 0.15); color: #2563eb; font-weight: 700;">
                                {{ $unitCount }} {{ $unitCount === 1 ? 'Einheit' : 'Einheiten' }}
                            </span>
                        </div>
                        <div style="font-size: 12px; color: var(--text-muted); font-weight: 500; margin-bottom: 14px;">
                            {{ $property->street }}, {{ $property->postal_code }} {{ $property->city }}
                        </div>
                    </div>

                    <div style="border-top: 1px solid var(--border-color); padding-top: 10px; display: flex; justify-content: space-between; align-items: center;">
                        <div style="font-size: 12px; color: var(--text-muted); font-weight: 500;">
                            Vermietet: <strong style="color: var(--text-main); font-weight: 700;">{{ $occupied }} / {{ $unitCount }}</strong> ({{ $rate }}%)
                        </div>
                        <div style="width: 80px; height: 6px; background: rgba(148, 163, 184, 0.2); border-radius: 3px; overflow: hidden;">
                            <div style="height: 100%; width: {{ $rate }}%; background: #16a34a; border-radius: 3px;"></div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

<!-- Tickets & Nachrichten Grid -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(360px, 1fr)); gap: 24px; margin-bottom: 24px;">
    <!-- Aktuelle Meldungen & Tickets -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <span>Aktuelle Schadensmeldungen & Tickets</span>
            </div>
            <div style="display: flex; gap: 8px;">
                <a href="{{ route('portal.tickets.create') }}" class="btn btn-primary" style="font-size: 12px; padding: 5px 10px;">+ Ticket erstellen</a>
                <a href="{{ route('portal.tickets.index') }}" class="btn btn-outline" style="font-size: 12px; padding: 5px 10px;">Alle anzeigen</a>
            </div>
        </div>

        @if($recentTickets->isEmpty())
            <div style="text-align: center; padding: 32px 16px; color: var(--text-muted);">
                <div style="font-size: 28px; margin-bottom: 8px;">✨</div>
                <div>Keine offenen Tickets vorhanden.</div>
            </div>
        @else
            <div style="display: flex; flex-direction: column; gap: 10px;">
                @foreach($recentTickets as $ticket)
                    <a href="{{ route('portal.tickets.show', $ticket) }}" class="sub-card" style="display: block; text-decoration: none; padding: 12px; transition: all 0.2s ease;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 4px;">
                            <span style="font-weight: 700; color: var(--text-main); font-size: 14px;">{{ $ticket->title }}</span>
                            <span class="badge" style="background: {{ match($ticket->status) {
                                'open' => 'rgba(239, 68, 68, 0.15)',
                                'in_progress' => 'rgba(245, 158, 11, 0.15)',
                                'resolved', 'closed' => 'rgba(16, 185, 129, 0.15)',
                                default => 'rgba(148, 163, 184, 0.15)'
                            } }}; color: {{ match($ticket->status) {
                                'open' => '#b91c1c',
                                'in_progress' => '#b45309',
                                'resolved', 'closed' => '#047857',
                                default => '#334155'
                            } }}; font-weight: 700;">
                                {{ ucfirst($ticket->status) }}
                            </span>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 12px; color: var(--text-muted); font-weight: 500;">
                            <span>{{ $ticket->property->name ?? 'Allgemein' }} &bull; Mieter: {{ $ticket->renter ? "{$ticket->renter->first_name} {$ticket->renter->last_name}" : ($ticket->creator?->name ?? 'Unbekannt') }}</span>
                            <span>{{ $ticket->created_at->format('d.m.Y H:i') }}</span>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Letzte Nachrichten -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <span>Nachrichtenverlauf</span>
            </div>
            <a href="{{ route('portal.messages.index') }}" class="btn btn-outline" style="font-size: 12px; padding: 5px 10px;">Zum Postfach</a>
        </div>

        @if($recentMessages->isEmpty())
            <div style="text-align: center; padding: 32px 16px; color: var(--text-muted);">
                <div style="font-size: 28px; margin-bottom: 8px;">💬</div>
                <div>Keine neuen Nachrichten.</div>
            </div>
        @else
            <div style="display: flex; flex-direction: column; gap: 10px;">
                @foreach($recentMessages as $msg)
                    @php
                        $isOutgoing = $msg->from_user_id === auth()->id();
                        $otherUser = $isOutgoing ? $msg->recipient : $msg->sender;
                    @endphp
                    <a href="{{ route('portal.messages.index') }}" class="sub-card" style="display: block; text-decoration: none; padding: 12px; transition: all 0.15s ease;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                            <span style="font-weight: 700; color: var(--text-main); font-size: 13px;">
                                {{ $isOutgoing ? 'An: ' . ($otherUser->name ?? 'Unbekannt') : 'Von: ' . ($otherUser->name ?? 'Unbekannt') }}
                            </span>
                            <span style="font-size: 11px; color: var(--text-faint);">{{ $msg->created_at->diffForHumans() }}</span>
                        </div>
                        <div style="font-size: 13px; color: var(--text-muted); overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                            {{ $msg->body }}
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</div>
