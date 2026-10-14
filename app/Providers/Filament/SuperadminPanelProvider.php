<?php

namespace App\Providers\Filament;

use App\Http\Middleware\EnsureSuperAdmin;
use App\Http\Middleware\SetLocale;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Auth\MultiFactor\Email\EmailAuthentication;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * Backoffice of the platform operator: all property managements, all users, sign-ups.
 *
 * Runs without an active property management, so every record of the platform is visible.
 */
class SuperadminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('superadmin')
            ->path('superadmin')
            ->brandName('Liegenschaftsportal.ch · Betrieb')
            ->brandLogo(fn () => view('filament.brand', ['subtitle' => 'Betrieb']))
            ->brandLogoHeight('2.5rem')
            ->favicon(asset('favicon.svg'))
            ->colors([
                'primary' => '#121212',
                'danger' => '#e2231a',
                'gray' => '#6b7280',
                'success' => '#16a34a',
                'warning' => '#d97706',
            ])
            ->multiFactorAuthentication(
                [
                    AppAuthentication::make()->brandName('Liegenschaftsportal.ch')->recoverable(),
                    EmailAuthentication::make(),
                ],
                isRequired: fn (): bool => (bool) config('app.admin_mfa_required'),
            )
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn () => new HtmlString('<link rel="stylesheet" href="'.asset('css/admin.css').'">')
            )
            ->discoverResources(in: app_path('Filament/Superadmin/Resources'), for: 'App\\Filament\\Superadmin\\Resources')
            ->discoverWidgets(in: app_path('Filament/Superadmin/Widgets'), for: 'App\\Filament\\Superadmin\\Widgets')
            ->pages([
                Dashboard::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                SetLocale::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                EnsureSuperAdmin::class,
            ]);
    }
}
