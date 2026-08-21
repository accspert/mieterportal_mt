@extends('portal.layout')

@section('title', 'Ticket #' . $ticket->id)
@section('page-title', 'Ticket Details #' . $ticket->id)

@section('content')
<div style="max-width: 860px; margin: 0 auto;">
    <div style="margin-bottom: 20px;">
        <a href="{{ route('portal.tickets.index') }}" class="btn btn-outline" style="font-size: 12px; padding: 5px 12px;">
            &larr; Zurück zur Ticketliste
        </a>
    </div>

    <div class="card" style="margin-bottom: 24px;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; flex-wrap: wrap; gap: 16px;">
            <div>
                <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 4px;">Ticket #T-{{ $ticket->id }} &bull; Erstellt am {{ $ticket->created_at->format('d.m.Y H:i') }}</div>
                <h1 style="font-size: 24px;">{{ $ticket->title }}</h1>
            </div>
            <div style="display: flex; gap: 8px; align-items: center;">
                <span class="badge badge-{{ $ticket->priority }}">{{ ucfirst($ticket->priority) }}</span>
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
            </div>
        </div>

        <div style="background: rgba(0,0,0,0.25); border-radius: var(--radius-md); padding: 16px; margin-bottom: 24px; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; font-size: 13px;">
            <div>
                <span style="color: var(--text-muted); display: block; font-size: 11px; text-transform: uppercase;">Liegenschaft / Einheit</span>
                <strong>{{ $ticket->property->name ?? '-' }} {{ $ticket->unit ? '(Einheit ' . $ticket->unit->number . ')' : '' }}</strong>
            </div>
            <div>
                <span style="color: var(--text-muted); display: block; font-size: 11px; text-transform: uppercase;">Kategorie</span>
                <strong style="text-transform: capitalize;">{{ $ticket->category ?? 'Allgemein' }}</strong>
            </div>
            <div>
                <span style="color: var(--text-muted); display: block; font-size: 11px; text-transform: uppercase;">Zuständiger Sachbearbeiter</span>
                <strong>{{ $ticket->assignee->name ?? 'Wird zugewiesen' }}</strong>
            </div>
        </div>

        <div style="margin-bottom: 24px;">
            <h4 style="font-size: 14px; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.05em; margin-bottom: 8px;">Beschreibung</h4>
            <div style="background: rgba(255, 255, 255, 0.02); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 16px; font-size: 14px; line-height: 1.6; white-space: pre-wrap;">{{ $ticket->description }}</div>
        </div>

        @if($ticket->attachments && $ticket->attachments->isNotEmpty())
            <div>
                <h4 style="font-size: 14px; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.05em; margin-bottom: 12px;">Angehängte Dateien & Fotos ({{ $ticket->attachments->count() }})</h4>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 12px;">
                    @foreach($ticket->attachments as $attachment)
                        <div style="background: rgba(0,0,0,0.3); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 12px; display: flex; align-items: center; gap: 10px;">
                            <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 20px; height: 20px; color: var(--color-primary-500); flex-shrink: 0;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 01-6.364-6.364l10.94-10.94A3 3 0 1119.5 7.373L8.553 18.32a1.5 1.5 0 01-2.122-2.122l9.82-9.82" />
                            </svg>
                            <div style="overflow: hidden; flex: 1;">
                                <div style="font-size: 12px; font-weight: 600; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;">{{ $attachment->filename }}</div>
                                <div style="font-size: 11px; color: var(--text-faint);">{{ number_format(($attachment->size ?? 0) / 1024, 1) }} KB</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    <!-- Communication for this ticket -->
    <div class="card">
        <h3 style="font-size: 18px; margin-bottom: 16px;">Rückfrage zu diesem Vorgang</h3>
        <form method="POST" action="{{ route('portal.messages.store') }}">
            @csrf
            <input type="hidden" name="subject" value="Rückfrage zu Ticket #T-{{ $ticket->id }}: {{ $ticket->title }}">
            <div class="form-group">
                <textarea name="body" class="form-textarea" rows="3" placeholder="Haben Sie weitere Informationen oder Rückfragen zum Ticket?" required></textarea>
            </div>
            <div style="display: flex; justify-content: flex-end;">
                <button type="submit" class="btn btn-primary">
                    <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" />
                    </svg>
                    Nachricht senden
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
