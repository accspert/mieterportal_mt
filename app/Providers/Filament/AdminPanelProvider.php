<?php

namespace App\Providers\Filament;

use App\Filament\Resources\ActivityResource;
use App\Filament\Resources\AnnouncementResource;
use App\Filament\Resources\DocumentResource;
use App\Filament\Resources\InvoiceResource;
use App\Filament\Resources\MessageResource;
use App\Filament\Resources\OwnerResource;
use App\Filament\Resources\PropertyResource;
use App\Filament\Resources\RenterResource;
use App\Filament\Resources\TaskResource;
use App\Filament\Resources\TicketResource;
use App\Filament\Resources\UnitResource;
use App\Filament\Resources\UserResource;
use App\Filament\Widgets\LatestTicketsWidget;
use App\Filament\Widgets\MonthlyRevenueChartWidget;
use App\Filament\Widgets\StatsOverviewWidget;
use App\Filament\Widgets\TicketStatusChartWidget;
use App\Http\Middleware\EnsureAdminPanelAccess;
use App\Http\Middleware\EnsureTenantIsActive;
use App\Http\Middleware\InitializeTenancyFromUser;
use App\Http\Middleware\SetLocale;
use App\Services\ImpersonationService;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Auth\MultiFactor\Email\EmailAuthentication;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
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

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->brandName('Liegenschaftsportal.ch')
            ->brandLogo(fn () => view('filament.brand'))
            ->brandLogoHeight('2.5rem')
            ->favicon(asset('favicon.svg'))
            ->passwordReset()
            ->multiFactorAuthentication(
                [
                    AppAuthentication::make()
                        ->brandName('Liegenschaftsportal.ch')
                        ->recoverable(),
                    EmailAuthentication::make(),
                ],
                // A super admin working in the account of an administrator (support) is not asked to set up
                // that person's second factor.
                isRequired: fn (): bool => (bool) config('app.admin_mfa_required') && ! ImpersonationService::isImpersonating(),
            )
            ->colors([
                'primary' => '#0056B3',
                'danger' => '#dc2626',
                'gray' => '#6b7280',
                'success' => '#16a34a',
                'warning' => '#d97706',
            ])
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn () => new HtmlString('<link rel="stylesheet" href="'.asset('css/admin.css').'">')
            )
            ->renderHook(
                PanelsRenderHook::BODY_START,
                fn () => view('partials.impersonation-banner')
            )
            ->renderHook(
                PanelsRenderHook::FOOTER,
                fn () => view('legal.partials.footer-links')
            )
            ->renderHook(
                PanelsRenderHook::SIMPLE_PAGE_END,
                fn () => view('legal.partials.footer-links')
            )
            ->renderHook(
                PanelsRenderHook::USER_MENU_BEFORE,
                fn () => new HtmlString(
                    '<div style="display:flex;align-items:center;margin-right:8px;background:var(--bg-surface);border:1px solid var(--border-color);border-radius:6px;padding:2px;">'.
                    '<a href="'.route('locale.switch', 'de').'" style="padding:2px 6px;font-size:11px;font-weight:700;border-radius:4px;text-decoration:none;'.(app()->getLocale() === 'de' ? 'background:var(--color-primary-600);color:#fff;' : 'color:var(--text-muted);').'">🇩🇪 DE</a>'.
                    '<a href="'.route('locale.switch', 'en').'" style="padding:2px 6px;font-size:11px;font-weight:700;border-radius:4px;text-decoration:none;'.(app()->getLocale() === 'en' ? 'background:var(--color-primary-600);color:#fff;' : 'color:var(--text-muted);').'">🇬🇧 EN</a>'.
                    '</div>'
                )
            )
            ->userMenuItems([
                MenuItem::make()
                    ->label(fn () => app()->getLocale() === 'de' ? 'English' : 'Deutsch')
                    ->url(fn () => route('locale.switch', app()->getLocale() === 'de' ? 'en' : 'de'))
                    ->icon('heroicon-o-language'),
            ])
            ->navigationGroups([
                __('Objekte'),
                __('Mieter & Kontakte'),
                __('Dokumente'),
                __('Tickets'),
                __('Aufgaben & Kommunikation'),
                __('Finanzen'),
                __('Administration'),
            ])
            ->pages([
                Dashboard::class,
            ])
            ->widgets([
                StatsOverviewWidget::class,
                TicketStatusChartWidget::class,
                MonthlyRevenueChartWidget::class,
                LatestTicketsWidget::class,
            ])
            ->resources([
                OwnerResource::class,
                PropertyResource::class,
                UnitResource::class,
                RenterResource::class,
                TicketResource::class,
                DocumentResource::class,
                MessageResource::class,
                TaskResource::class,
                InvoiceResource::class,
                AnnouncementResource::class,
                UserResource::class,
                ActivityResource::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                // Must run before Filament's Authenticate (which Laravel's middleware priority moves up front,
                // right before InitializeTenancyFromUser): renters are redirected to the portal instead of
                // hitting the 403 from canAccessPanel(). Roles are shared, so no tenant is needed here.
                EnsureAdminPanelAccess::class,
                // The logged-in user determines the property management (one shared database).
                InitializeTenancyFromUser::class,
                EnsureTenantIsActive::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                SetLocale::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
