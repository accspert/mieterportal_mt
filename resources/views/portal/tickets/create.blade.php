@extends('portal.layout')

@section('title', 'Neuen Schaden melden')
@section('page-title', 'Neuen Schaden melden')

@section('content')
<div style="max-width: 760px; margin: 0 auto;">
    <div style="margin-bottom: 24px;">
        <a href="{{ route('portal.tickets.index') }}" class="btn btn-outline" style="font-size: 12px; padding: 5px 12px; margin-bottom: 12px;">
            &larr; Zurück zur Übersicht
        </a>
        <h1 style="font-size: 24px; margin-bottom: 6px;">Schaden oder Service-Anliegen melden</h1>
        <p style="color: var(--text-muted); font-size: 14px;">Bitte beschreiben Sie das Problem so genau wie möglich, damit wir schnell handeln können.</p>
    </div>

    <div class="card">
        @if ($errors->any())
            <div class="alert alert-danger">
                <div>
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            </div>
        @endif

        <form method="POST" action="{{ route('portal.tickets.store') }}" enctype="multipart/form-data">
            @csrf

            <div class="form-group">
                <label class="form-label" for="title">Titel / Kurzbeschreibung *</label>
                <input type="text" id="title" name="title" class="form-control" placeholder="z.B. Heizkörper im Wohnzimmer wird nicht warm" value="{{ old('title') }}" required>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
                <div class="form-group">
                    <label class="form-label" for="category">Kategorie *</label>
                    <select id="category" name="category" class="form-select" required>
                        <option value="repair" {{ old('category') == 'repair' ? 'selected' : '' }}>Reparatur / Handwerker</option>
                        <option value="general" {{ old('category') == 'general' ? 'selected' : '' }}>Allgemeines Anliegen</option>
                        <option value="billing" {{ old('category') == 'billing' ? 'selected' : '' }}>Abrechnung / Miete</option>
                        <option value="other" {{ old('category') == 'other' ? 'selected' : '' }}>Sonstiges</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="priority">Dringlichkeit *</label>
                    <select id="priority" name="priority" class="form-select" required>
                        <option value="low" {{ old('priority') == 'low' ? 'selected' : '' }}>Niedrig (keine Eile)</option>
                        <option value="medium" {{ old('priority', 'medium') == 'medium' ? 'selected' : '' }}>Normal (in den nächsten Tagen)</option>
                        <option value="high" {{ old('priority') == 'high' ? 'selected' : '' }}>Hoch (zeitnah erforderlich)</option>
                        <option value="urgent" {{ old('priority') == 'urgent' ? 'selected' : '' }}>Dringend / Notfall (z.B. Rohrbruch)</option>
                    </select>
                </div>
            </div>

            @if($availableUnits->isNotEmpty())
                <div class="form-group">
                    <label class="form-label" for="unit_id">Betroffene Mieteinheit / Wohnung *</label>
                    <select id="unit_id" name="unit_id" class="form-select" required>
                        @foreach($availableUnits as $unit)
                            <option value="{{ $unit->id }}" {{ old('unit_id', request('unit_id')) == $unit->id ? 'selected' : '' }}>
                                Einheit {{ $unit->number }} – {{ $unit->property->name ?? 'Liegenschaft' }} ({{ $unit->property->street ?? '' }}, {{ $unit->property->city ?? '' }})
                            </option>
                        @endforeach
                    </select>
                    <div style="font-size: 12px; color: var(--text-muted); margin-top: 5px;">
                        <svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 14px; height: 14px; display: inline-block; vertical-align: -2px;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                        </svg>
                        Das Gebäude, die Wohnung und Ihr Name werden automatisch an den zuständigen Sachbearbeiter übermittelt.
                    </div>
                </div>
            @elseif($availableProperties->isNotEmpty())
                <div class="form-group">
                    <label class="form-label" for="property_id">Betroffenes Objekt *</label>
                    <select id="property_id" name="property_id" class="form-select" required>
                        @foreach($availableProperties as $property)
                            <option value="{{ $property->id }}" {{ old('property_id') == $property->id ? 'selected' : '' }}>
                                {{ $property->name }} ({{ $property->street }}, {{ $property->postal_code }} {{ $property->city }})
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="form-group">
                <label class="form-label" for="description">Detaillierte Beschreibung *</label>
                <textarea id="description" name="description" class="form-textarea" rows="6" placeholder="Beschreiben Sie hier den Schaden, wann er aufgetreten ist und ob ein Handwerkertermin erforderlich ist..." required>{{ old('description') }}</textarea>
            </div>

            <div class="form-group">
                <label class="form-label" for="attachments">Fotos / Belege / Dokumente anhängen (optional)</label>
                <input type="file" id="attachments" name="attachments[]" class="form-control" multiple accept="image/*,.pdf,.doc,.docx">
                <div style="font-size: 11px; color: var(--text-faint); margin-top: 4px;">Unterstützte Formate: JPG, PNG, WEBP, PDF (max. 10 MB pro Datei)</div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px;">
                <a href="{{ route('portal.tickets.index') }}" class="btn btn-outline">Abbrechen</a>
                <button type="submit" class="btn btn-primary">
                    <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" />
                    </svg>
                    Meldung einreichen
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
