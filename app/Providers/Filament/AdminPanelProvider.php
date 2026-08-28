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
use App\Http\Middleware\SetLocale;
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
use Stancl\Tenancy\Middleware\InitializeTenancyByDomainOrSubdomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->brandName('Mieterportal')
            ->brandLogo(fn () => view('filament.brand'))
            ->brandLogoHeight('2.5rem')
            ->colors([
                'primary' => '#0056B3',
                'danger' => '#dc2626',
                'gray' => '#6b7280',
                'success' => '#16a34a',
                'warning' => '#d97706',
            ])
            ->renderHook(
                PanelsRenderHook::HEAD_START,
                fn () => new HtmlString(
                    '<link rel="preconnect" href="https://fonts.googleapis.com">'.
                    '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>'
                )
            )
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn () => new HtmlString('<link rel="stylesheet" href="'.asset('css/admin.css').'">')
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
                InitializeTenancyByDomainOrSubdomain::class,
                PreventAccessFromCentralDomains::class,
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
                EnsureAdminPanelAccess::class,
            ]);
    }
}
