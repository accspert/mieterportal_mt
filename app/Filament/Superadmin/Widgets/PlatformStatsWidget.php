<?php

namespace App\Filament\Superadmin\Widgets;

use App\Models\CompanyTenant;
use App\Models\TenantRegistration;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Key figures of the platform on the backoffice dashboard.
 */
class PlatformStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $tenantCount = CompanyTenant::query()->count();
        $activeTenantCount = CompanyTenant::query()->where('is_active', true)->count();

        return [
            Stat::make('Verwaltungen', $tenantCount)
                ->description("{$activeTenantCount} aktiv, ".($tenantCount - $activeTenantCount).' gesperrt'),
            Stat::make('Benutzer', User::withoutGlobalScopes()->count())
                ->description('auf der ganzen Plattform'),
            Stat::make('Neue Registrierungen', TenantRegistration::query()->where('created_at', '>=', now()->subDays(30))->count())
                ->description('in den letzten 30 Tagen'),
            Stat::make('Unbestätigt', TenantRegistration::query()->whereNull('confirmed_at')->count())
                ->description('warten auf E-Mail-Bestätigung'),
        ];
    }
}
