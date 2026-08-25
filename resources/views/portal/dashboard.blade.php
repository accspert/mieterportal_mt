@extends('portal.layout')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
<div style="margin-bottom: 28px;">
    <h1 style="font-size: 28px; margin-bottom: 6px;">
        Guten Tag, {{ $user->name }} 👋
    </h1>
    <p style="color: var(--text-muted); font-size: 14px;">
        @if($owner || $user->hasRole('Eigentümer'))
            Willkommen in Ihrem Eigentümerportal. Hier verwalten Sie Ihre Liegenschaften, Einheiten und Abrechnungen.
        @else
            Willkommen in Ihrem Mieterportal. Hier finden Sie alle Informationen zu Ihrem Mietverhältnis, Rechnungen und Vorgänge.
        @endif
    </p>
</div>

<!-- Announcements / Bulletin Board -->
@if(isset($announcements) && $announcements->isNotEmpty())
    <div style="margin-bottom: 24px; display: flex; flex-direction: column; gap: 12px;">
        @foreach($announcements as $announcement)
            <div class="card" style="padding: 16px 20px; border-left: 4px solid {{ match($announcement->type) {
                'urgent' => '#ef4444',
                'warning' => '#f59e0b',
                default => '#3b82f6',
            } }};">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; flex-wrap: wrap; gap: 8px;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <span class="badge" style="background: {{ match($announcement->type) {
                            'urgent' => '#fee2e2',
                            'warning' => '#fef3c7',
                            default => '#dbeafe',
                        } }}; color: {{ match($announcement->type) {
                            'urgent' => '#991b1b',
                            'warning' => '#92400e',
                            default => '#1e40af',
                        } }}; font-weight: 700;">
                            {{ match($announcement->type) {
                                'urgent' => 'Dringender Aushang',
                                'warning' => 'Wichtiger Hinweis',
                                default => 'Haus-Mitteilung',
                            } }}
                        </span>
                        <strong style="font-size: 15px; font-weight: 700; color: var(--text-main);">{{ $announcement->title }}</strong>
                    </div>
                    <div style="font-size: 12px; font-weight: 500; color: var(--text-muted);">
                        {{ $announcement->property ? 'Objekt: ' . $announcement->property->name : 'Alle Liegenschaften' }}
                        @if($announcement->starts_at)
                            &bull; {{ $announcement->starts_at->format('d.m.Y') }}
                        @endif
                    </div>
                </div>
                <div style="font-size: 13px; line-height: 1.6; color: var(--text-main); white-space: pre-wrap;">{{ $announcement->body }}</div>
            </div>
        @endforeach
    </div>
@endif

<!-- Dynamic Role Dashboard View -->
@if($user->hasAnyRole(['Administrator', 'Sachbearbeiter']))
    @include('portal.dashboards.staff')
@elseif($owner || $user->hasRole('Eigentümer'))
    @include('portal.dashboards.owner')
@else
    @include('portal.dashboards.tenant')
@endif

@endsection
