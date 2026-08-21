@extends('portal.layout')

@section('title', 'Meine Dokumente')
@section('page-title', 'Dokumentenablage')

@section('content')
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
    <div>
        <h1 style="font-size: 24px; margin-bottom: 4px;">Meine Dokumente & Verträge</h1>
        <p style="color: var(--text-muted); font-size: 14px;">Mietverträge, Abrechnungen, Hausordnungen und Protokolle digital einsehen.</p>
    </div>
</div>

<!-- Filters & Search -->
<div class="card" style="padding: 16px; margin-bottom: 24px;">
    <form method="GET" action="{{ route('portal.documents.index') }}" style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
        <input type="text" name="search" class="form-control" style="max-width: 280px;" placeholder="Dokument suchen..." value="{{ request('search') }}">
        
        <select name="category" class="form-select" style="max-width: 200px;" onchange="this.form.submit()">
            <option value="">Alle Kategorien</option>
            <option value="contract" {{ request('category') === 'contract' ? 'selected' : '' }}>Vertrag</option>
            <option value="invoice" {{ request('category') === 'invoice' ? 'selected' : '' }}>Abrechnung / Rechnung</option>
            <option value="protocol" {{ request('category') === 'protocol' ? 'selected' : '' }}>Protokoll</option>
            <option value="general" {{ request('category') === 'general' ? 'selected' : '' }}>Allgemein</option>
        </select>

        <button type="submit" class="btn btn-secondary" style="padding: 10px 16px;">Suchen</button>
        @if(request()->hasAny(['search', 'category']))
            <a href="{{ route('portal.documents.index') }}" class="btn btn-outline" style="padding: 10px 16px;">Zurücksetzen</a>
        @endif
    </form>
</div>

<div class="card" style="padding: 0;">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Dateiname</th>
                    <th>Kategorie</th>
                    <th>Version</th>
                    <th>Hinzugefügt am</th>
                    <th style="text-align: right;">Aktion</th>
                </tr>
            </thead>
            <tbody>
                @forelse($documents as $doc)
                    <tr>
                        <td>
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <div style="width: 36px; height: 36px; border-radius: var(--radius-sm); background: rgba(239, 68, 68, 0.15); color: #f87171; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 11px;">
                                    PDF
                                </div>
                                <div>
                                    <div style="font-weight: 600; color: #fff;">{{ $doc->filename }}</div>
                                    <div style="font-size: 11px; color: var(--text-muted);">{{ $doc->mime_type ?? 'application/pdf' }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge badge-info" style="text-transform: capitalize;">
                                {{ match($doc->category) {
                                    'contract' => 'Vertrag',
                                    'invoice' => 'Abrechnung',
                                    'protocol' => 'Protokoll',
                                    'general' => 'Allgemein',
                                    default => $doc->category
                                } }}
                            </span>
                        </td>
                        <td style="color: var(--text-muted);">v{{ $doc->version ?? 1 }}</td>
                        <td style="color: var(--text-muted); font-size: 13px;">{{ $doc->created_at->format('d.m.Y') }}</td>
                        <td style="text-align: right;">
                            <a href="{{ route('portal.documents.download', $doc) }}" class="btn btn-outline" style="padding: 6px 12px; font-size: 12px;">
                                <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 14px; height: 14px;">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                </svg>
                                Herunterladen
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 40px; color: var(--text-muted);">
                            Keine Dokumente in diesem Bereich vorhanden.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($documents->hasPages())
        <div style="padding: 16px 20px; border-top: 1px solid var(--border-color);">
            {{ $documents->links() }}
        </div>
    @endif
</div>
@endsection
