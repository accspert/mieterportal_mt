@extends('portal.layout')

@section('title', 'Meine Einheiten & Objekte')
@section('page-title', 'Meine Einheiten & Objekte')

@section('content')
<div style="margin-bottom: 24px;">
    <h1 style="font-size: 24px; margin-bottom: 6px;">Objekt- & Einheitenübersicht</h1>
    <p style="color: var(--text-muted); font-size: 14px;">Alle Details zu Ihren gemieteten oder verwalteten Immobilien.</p>
</div>

@if($tenant && $units->isNotEmpty())
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 24px;">
        @foreach($units as $unit)
            <div class="card">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px;">
                    <div>
                        <div style="font-size: 11px; text-transform: uppercase; font-weight: 700; color: var(--color-primary-500); margin-bottom: 4px;">Gemietete Einheit</div>
                        <h3 style="font-size: 20px;">Einheit {{ $unit->number }}</h3>
                        <div style="color: var(--text-muted); font-size: 13px; margin-top: 2px;">
                            {{ $unit->property->name }} &bull; {{ $unit->property->street }}, {{ $unit->property->postal_code }} {{ $unit->property->city }}
                        </div>
                    </div>
                    <span class="badge badge-resolved">Aktiv</span>
                </div>

                <div class="sub-card" style="border-radius: var(--radius-md); padding: 16px; margin-bottom: 20px; display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; font-size: 13px;">
                    <div>
                        <span style="color: var(--text-muted); display: block; font-size: 11px; font-weight: 600; text-transform: uppercase;">Etage</span>
                        <strong style="color: var(--text-main); font-weight: 700;">{{ $unit->floor ?? 'EG' }}</strong>
                    </div>
                    <div>
                        <span style="color: var(--text-muted); display: block; font-size: 11px; font-weight: 600; text-transform: uppercase;">Wohnfläche</span>
                        <strong style="color: var(--text-main); font-weight: 700;">{{ $unit->area ? $unit->area . ' m²' : '-' }}</strong>
                    </div>
                    <div>
                        <span style="color: var(--text-muted); display: block; font-size: 11px; font-weight: 600; text-transform: uppercase;">Zimmer</span>
                        <strong style="color: var(--text-main); font-weight: 700;">{{ $unit->rooms ?? '-' }}</strong>
                    </div>
                    <div>
                        <span style="color: var(--text-muted); display: block; font-size: 11px; font-weight: 600; text-transform: uppercase;">Einzug</span>
                        <strong style="color: var(--text-main); font-weight: 700;">{{ $tenant->move_in ? date('d.m.Y', strtotime($tenant->move_in)) : 'Vertrag aktiv' }}</strong>
                    </div>
                </div>

                <div style="display: flex; gap: 10px;">
                    <a href="{{ route('portal.tickets.create') }}?unit_id={{ $unit->id }}" class="btn btn-primary" style="flex: 1; font-size: 13px;">
                        Schaden melden
                    </a>
                    <a href="{{ route('portal.documents.index') }}" class="btn btn-secondary" style="font-size: 13px;">
                        Dokumente
                    </a>
                </div>
            </div>
        @endforeach
    </div>
@elseif($owner && $properties->isNotEmpty())
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 24px;">
        @foreach($properties as $property)
            <div class="card">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px;">
                    <div>
                        <div style="font-size: 11px; text-transform: uppercase; font-weight: 700; color: var(--color-accent-500); margin-bottom: 4px;">Eigentumsobjekt</div>
                        <h3 style="font-size: 20px;">{{ $property->name }}</h3>
                        <div style="color: var(--text-muted); font-size: 13px; margin-top: 2px;">
                            {{ $property->street }}, {{ $property->postal_code }} {{ $property->city }}
                        </div>
                    </div>
                    <span class="badge badge-info">{{ $property->units->count() }} Einheiten</span>
                </div>

                <div style="background: rgba(0,0,0,0.25); border-radius: var(--radius-md); padding: 16px; margin-bottom: 20px; font-size: 13px;">
                    <div style="color: var(--text-muted); margin-bottom: 8px; font-size: 12px; font-weight: 600;">Verfügbare Einheiten:</div>
                    <ul style="list-style: none; padding: 0; display: flex; flex-direction: column; gap: 6px;">
                        @foreach($property->units as $unit)
                            <li style="display: flex; justify-content: space-between; color: var(--text-main);">
                                <span>Einheit {{ $unit->number }} ({{ $unit->floor ?? 'EG' }}, {{ $unit->area ?? '-' }} m²)</span>
                                <span style="color: var(--text-muted);">
                                    {{ $unit->currentTenant ? $unit->currentTenant->first_name . ' ' . $unit->currentTenant->last_name : 'Leerstand' }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <a href="{{ route('portal.documents.index') }}" class="btn btn-secondary" style="width: 100%; font-size: 13px;">
                    Objektdokumente einsehen
                </a>
            </div>
        @endforeach
    </div>
@else
    <div class="card" style="text-align: center; padding: 48px 24px;">
        <div style="font-size: 36px; margin-bottom: 12px;">🏢</div>
        <h3 style="font-size: 18px; margin-bottom: 6px;">Keine aktiven Einheiten zugewiesen</h3>
        <p style="color: var(--text-muted); font-size: 13px; max-width: 480px; margin: 0 auto 20px;">
            Ihr Benutzerkonto ist derzeit noch keinem aktiven Miet- oder Eigentumsverhältnis zugeordnet. Bitte kontaktieren Sie Ihre Hausverwaltung.
        </p>
        <a href="{{ route('portal.messages.index') }}" class="btn btn-primary">Nachricht an Hausverwaltung</a>
    </div>
@endif
@endsection
