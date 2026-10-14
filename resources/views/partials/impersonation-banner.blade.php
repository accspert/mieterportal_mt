@if (\App\Services\ImpersonationService::isImpersonating() && auth()->check())
    <div role="status" style="position: sticky; top: 0; z-index: 9999; display: flex; flex-wrap: wrap; gap: 8px 16px; align-items: center; justify-content: center; padding: 10px 16px; background: #e2231a; color: #fff; font: 600 14px/1.3 system-ui, sans-serif; text-align: center;">
        <span>Support-Modus: Sie arbeiten im Konto von {{ auth()->user()->name }} ({{ auth()->user()->email }}).</span>
        <a href="{{ route('impersonation.stop') }}" style="padding: 6px 14px; border-radius: 999px; background: #fff; color: #121212; text-decoration: none;">Zurück zum Backoffice</a>
    </div>
@endif
