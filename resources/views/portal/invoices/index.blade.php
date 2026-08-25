@extends('portal.layout')

@section('title', 'Meine Rechnungen')
@section('page-title', 'Rechnungen')

@section('content')
<div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
    <div>
        <div style="display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 20px; background: rgba(59, 130, 246, 0.12); color: #60a5fa; font-size: 12px; font-weight: 600; margin-bottom: 8px;">
            <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 14px; height: 14px;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
            </svg>
            Finanz- & Abrechnungszentrum
        </div>
        <h1 style="font-size: 24px; margin-bottom: 4px; color: #fff;">Rechnungsübersicht & Zahlungen</h1>
        <p style="color: var(--text-muted); font-size: 14px;">Mietzahlungen, Nebenkostenabrechnungen und Einzelbelege auf einen Blick.</p>
    </div>
</div>

<!-- Financial Stats Grid -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 16px; margin-bottom: 24px;">
    <!-- Offene Forderungen -->
    <div class="stat-card stat-warning" style="background: linear-gradient(135deg, rgba(30, 41, 59, 0.7), rgba(15, 23, 42, 0.8)); border-left: 4px solid var(--color-warning-500);">
        <div class="stat-icon" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b;">
            <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 22px; height: 22px;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>
        <div class="stat-info">
            <div class="stat-value" style="color: #fbbf24;">{{ number_format($totals['open_amount'], 2, ',', '.') }} €</div>
            <div class="stat-label">Offen / Ausstehend ({{ $totals['open_count'] }} {{ $totals['open_count'] === 1 ? 'Rechnung' : 'Rechnungen' }})</div>
        </div>
    </div>

    <!-- Bereits beglichen -->
    <div class="stat-card stat-success" style="background: linear-gradient(135deg, rgba(30, 41, 59, 0.7), rgba(15, 23, 42, 0.8)); border-left: 4px solid var(--color-success-500);">
        <div class="stat-icon" style="background: rgba(16, 185, 129, 0.15); color: #10b981;">
            <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 22px; height: 22px;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>
        <div class="stat-info">
            <div class="stat-value" style="color: #34d399;">{{ number_format($totals['paid_amount'], 2, ',', '.') }} €</div>
            <div class="stat-label">Bereits beglichen ({{ $totals['paid_count'] }} {{ $totals['paid_count'] === 1 ? 'Rechnung' : 'Rechnungen' }})</div>
        </div>
    </div>

    <!-- Gesamtübersicht -->
    <div class="stat-card stat-accent" style="background: linear-gradient(135deg, rgba(30, 41, 59, 0.7), rgba(15, 23, 42, 0.8)); border-left: 4px solid var(--color-primary-500);">
        <div class="stat-icon" style="background: rgba(59, 130, 246, 0.15); color: #3b82f6;">
            <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 22px; height: 22px;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
            </svg>
        </div>
        <div class="stat-info">
            <div class="stat-value" style="color: #60a5fa;">{{ $totals['total_count'] }}</div>
            <div class="stat-label">Gesamte Rechnungsbelege</div>
        </div>
    </div>
</div>

<!-- Search & Filter Controls -->
<div class="card" style="padding: 16px 20px; margin-bottom: 20px;">
    <form method="GET" action="{{ route('portal.invoices.index') }}" style="display: flex; gap: 12px; align-items: center; justify-content: space-between; flex-wrap: wrap;">
        <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap; flex: 1; min-width: 280px;">
            <div style="position: relative; flex: 1; max-width: 320px;">
                <input type="text" name="search" class="form-control" placeholder="Rechnungsnr. oder Betreff suchen..." value="{{ request('search') }}" style="padding-left: 36px;">
                <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="position: absolute; left: 11px; top: 50%; transform: translateY(-50%); width: 16px; height: 16px; color: var(--text-muted);">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                </svg>
            </div>

            <select name="status" class="form-select" style="max-width: 200px;" onchange="this.form.submit()">
                <option value="">Alle Status</option>
                <option value="sent" {{ request('status') === 'sent' ? 'selected' : '' }}>Offen / Ausstehend</option>
                <option value="overdue" {{ request('status') === 'overdue' ? 'selected' : '' }}>Überfällig</option>
                <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Bezahlt</option>
                <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Entwurf</option>
            </select>

            <button type="submit" class="btn btn-secondary" style="padding: 9px 16px; font-size: 13px;">
                Filtern
            </button>

            @if(request()->hasAny(['search', 'status']))
                <a href="{{ route('portal.invoices.index') }}" class="btn btn-outline" style="padding: 9px 14px; font-size: 13px;">
                    ✕ Filter zurücksetzen
                </a>
            @endif
        </div>
    </form>
</div>

<!-- Invoices Table Card -->
<div class="card" style="padding: 0; overflow: hidden;">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th style="padding: 14px 20px;">Rechnung / Vorgang</th>
                    <th>Einheit</th>
                    <th>Fälligkeitsdatum</th>
                    <th>Betrag</th>
                    <th>Status</th>
                    <th style="text-align: right; padding-right: 20px;">Aktion</th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoices as $inv)
                    @php
                        // Clean display title from notes (remove [DocID:xxx] marker if present)
                        $displayTitle = $inv->notes ? preg_replace('/\[DocID:\d+\]/', '', $inv->notes) : null;
                        $displayTitle = trim($displayTitle ?? '');
                        $isOverdue = $inv->status === 'overdue' || ($inv->status === 'sent' && $inv->due_date && $inv->due_date->isPast());
                    @endphp
                    <tr>
                        <td style="padding: 14px 20px;">
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <div style="width: 38px; height: 38px; border-radius: 8px; background: rgba(59, 130, 246, 0.12); color: #60a5fa; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                    <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 18px; height: 18px;">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                    </svg>
                                </div>
                                <div>
                                    <div style="font-weight: 700; color: var(--text-main); font-size: 14px; letter-spacing: -0.01em;">
                                        {{ $inv->invoice_number }}
                                    </div>
                                    @if($displayTitle)
                                        <div style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">
                                            {{ $displayTitle }}
                                        </div>
                                    @else
                                        <div style="font-size: 11px; color: var(--text-faint); margin-top: 2px;">
                                            Erstellt am {{ $inv->created_at ? $inv->created_at->format('d.m.Y') : '-' }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>
                            @if($inv->unit)
                                <span style="display: inline-flex; align-items: center; gap: 4px; padding: 3px 8px; border-radius: 6px; background: rgba(0,0,0,0.04); border: 1px solid var(--border-color); font-size: 12px; color: var(--text-main); font-weight: 600;">
                                    <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 12px; height: 12px; color: var(--text-muted);">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                                    </svg>
                                    Einheit {{ $inv->unit->number }}
                                </span>
                            @else
                                <span style="color: var(--text-faint); font-size: 12px;">–</span>
                            @endif
                        </td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 6px; font-size: 13px; color: {{ $isOverdue ? '#ef4444' : 'var(--text-muted)' }}; font-weight: 500;">
                                <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 14px; height: 14px;">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.253 18.75A2.25 2.25 0 014.5 21.75h15a2.25 2.25 0 002.25-2.25V7.5A2.25 2.25 0 0019.5 5.25H4.5A2.25 2.25 0 002.25 7.5v12a2.25 2.25 0 002.25 2.25z" />
                                </svg>
                                <span>{{ $inv->due_date ? $inv->due_date->format('d.m.Y') : '-' }}</span>
                            </div>
                            @if($isOverdue)
                                <div style="font-size: 10px; color: #dc2626; font-weight: 700; margin-top: 2px;">Fälligkeit überschritten</div>
                            @endif
                        </td>
                        <td>
                            <div style="font-weight: 700; color: var(--text-main); font-size: 15px;">
                                {{ number_format($inv->amount, 2, ',', '.') }} €
                            </div>
                        </td>
                        <td>
                            @if($inv->status === 'paid')
                                <span class="badge" style="background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3);">
                                    ✓ Bezahlt
                                </span>
                            @elseif($inv->status === 'sent')
                                <span class="badge" style="background: rgba(245, 158, 11, 0.15); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.3);">
                                    Offen
                                </span>
                            @elseif($inv->status === 'overdue')
                                <span class="badge" style="background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3);">
                                    ! Überfällig
                                </span>
                            @elseif($inv->status === 'draft')
                                <span class="badge" style="background: rgba(148, 163, 184, 0.15); color: #cbd5e1; border: 1px solid rgba(148, 163, 184, 0.3);">
                                    Entwurf
                                </span>
                            @else
                                <span class="badge badge-{{ $inv->status }}">
                                    {{ ucfirst($inv->status) }}
                                </span>
                            @endif
                        </td>
                        <td style="text-align: right; padding-right: 20px;">
                            <a href="{{ route('portal.invoices.download', $inv) }}" target="_blank" class="btn btn-outline" style="padding: 7px 12px; font-size: 12px; display: inline-flex; align-items: center; gap: 6px;">
                                <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 14px; height: 14px;">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                </svg>
                                PDF Beleg
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 48px 20px; color: var(--text-muted);">
                            <div style="font-size: 36px; margin-bottom: 12px;">💳</div>
                            <div style="font-weight: 600; font-size: 16px; color: #fff; margin-bottom: 4px;">Keine Rechnungen gefunden</div>
                            <div style="font-size: 13px; max-width: 400px; margin: 0 auto;">
                                @if(request()->hasAny(['search', 'status']))
                                    Für Ihre aktuellen Filtereinstellungen wurden keine Rechnungen gefunden.
                                @else
                                    Aktuell liegen keine Rechnungen oder offenen Zahlungsaufforderungen für Ihr Konto vor.
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($invoices->hasPages())
        <div style="padding: 16px 20px; border-top: 1px solid var(--border-color); background: rgba(0,0,0,0.1);">
            {{ $invoices->links() }}
        </div>
    @endif
</div>
@endsection
