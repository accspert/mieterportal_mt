{{-- Brand of the admin panel and the backoffice: the "L." mark of the website and the platform name. --}}
<div class="filament-brand-header" style="display: flex; align-items: center; gap: 12px; text-decoration: none;">
    <img src="{{ asset('favicon.svg') }}" alt="" width="36" height="36" style="width: 36px; height: 36px; border-radius: 8px;">
    <div style="display: flex; flex-direction: column; text-align: left; line-height: 1.2;">
        <div class="logo-title" style="font-size: 16px; font-weight: 700; letter-spacing: -0.02em; color: var(--text-main);">
            Liegenschaftsportal.ch
        </div>
        <div class="logo-subtitle" style="font-size: 10px; text-transform: uppercase; letter-spacing: 0.08em; font-weight: 700; color: #e2231a !important;">
            {{ $subtitle ?? 'Verwaltung' }}
        </div>
    </div>
</div>
