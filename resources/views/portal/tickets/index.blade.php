@extends('portal.layout')

@section('title', 'Meine Schadenmeldungen & Tickets')
@section('page-title', 'Meldungen & Tickets')

@section('content')
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
    <div>
        <h1 style="font-size: 24px; margin-bottom: 4px;">Schadenmeldungen & Service-Anfragen</h1>
        <p style="color: var(--text-muted); font-size: 14px;">Übersicht aller gemeldeten Schäden und deren Bearbeitungsstand.</p>
    </div>
    <a href="{{ route('portal.tickets.create') }}" class="btn btn-primary">
        <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
        </svg>
        Neuen Schaden melden
    </a>
</div>

<!-- Filters -->
<div class="card" style="padding: 16px; margin-bottom: 24px; display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
    <span style="font-size: 13px; font-weight: 600; color: var(--text-muted); margin-right: 8px;">Statusfilter:</span>
    <a href="{{ route('portal.tickets.index') }}" class="btn {{ !request('status') ? 'btn-primary' : 'btn-outline' }}" style="padding: 6px 14px; font-size: 12px;">Alle</a>
    <a href="{{ route('portal.tickets.index', ['status' => 'open']) }}" class="btn {{ request('status') === 'open' ? 'btn-primary' : 'btn-outline' }}" style="padding: 6px 14px; font-size: 12px;">Offen</a>
    <a href="{{ route('portal.tickets.index', ['status' => 'in_progress']) }}" class="btn {{ request('status') === 'in_progress' ? 'btn-primary' : 'btn-outline' }}" style="padding: 6px 14px; font-size: 12px;">In Bearbeitung</a>
    <a href="{{ route('portal.tickets.index', ['status' => 'resolved']) }}" class="btn {{ request('status') === 'resolved' ? 'btn-primary' : 'btn-outline' }}" style="padding: 6px 14px; font-size: 12px;">Gelöst</a>
    <a href="{{ route('portal.tickets.index', ['status' => 'closed']) }}" class="btn {{ request('status') === 'closed' ? 'btn-primary' : 'btn-outline' }}" style="padding: 6px 14px; font-size: 12px;">Geschlossen</a>
</div>

<div class="card" style="padding: 0;">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Ticket / Betreff</th>
                    <th>Objekt / Einheit</th>
                    <th>Kategorie</th>
                    <th>Priorität</th>
                    <th>Status</th>
                    <th>Erstellt am</th>
                    <th style="text-align: right;">Aktion</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tickets as $ticket)
                    <tr>
                        <td>
                            <div style="font-weight: 600; color: #fff;">{{ $ticket->title }}</div>
                            <div style="font-size: 12px; color: var(--text-muted);">#T-{{ $ticket->id }}</div>
                        </td>
                        <td>
                            <div>{{ $ticket->property->name ?? '-' }}</div>
                            @if($ticket->unit)
                                <div style="font-size: 12px; color: var(--text-muted);">Einheit {{ $ticket->unit->number }}</div>
                            @endif
                        </td>
                        <td>
                            <span style="text-transform: capitalize; color: var(--text-muted); font-size: 13px;">
                                {{ $ticket->category ?? 'Allgemein' }}
                            </span>
                        </td>
                        <td>
                            <span class="badge badge-{{ $ticket->priority }}">
                                {{ ucfirst($ticket->priority) }}
                            </span>
                        </td>
                        <td>
                            <span class="badge badge-{{ $ticket->status }}">
                                {{ match($ticket->status) {
                                    'open' => 'Offen',
                                    'in_progress' => 'In Bearbeitung',
                                    'waiting' => 'Wartet',
                                    'done' => 'Erledigt',
                                    'resolved' => 'Gelöst',
                                    'closed' => 'Geschlossen',
                                    default => $ticket->status
                                } }}
                            </span>
                        </td>
                        <td style="color: var(--text-muted); font-size: 13px;">
                            {{ $ticket->created_at->format('d.m.Y H:i') }}
                        </td>
                        <td style="text-align: right;">
                            <a href="{{ route('portal.tickets.show', $ticket) }}" class="btn btn-outline" style="padding: 6px 12px; font-size: 12px;">
                                Details
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 40px; color: var(--text-muted);">
                            Keine Tickets in dieser Ansicht gefunden.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($tickets->hasPages())
        <div style="padding: 16px 20px; border-top: 1px solid var(--border-color);">
            {{ $tickets->links() }}
        </div>
    @endif
</div>
@endsection
