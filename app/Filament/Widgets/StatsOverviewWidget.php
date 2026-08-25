<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use App\Models\Property;
use App\Models\Ticket;
use App\Models\Unit;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totalProperties = Property::count();
        $totalUnits = Unit::count();
        $occupiedUnits = Unit::whereNotNull('current_renter_id')->count();
        $occupancyRate = $totalUnits > 0 ? round(($occupiedUnits / $totalUnits) * 100, 1) : 0;

        $openTickets = Ticket::whereIn('status', ['open', 'in_progress', 'waiting'])->count();
        $urgentTickets = Ticket::where('priority', 'urgent')->whereNotIn('status', ['resolved', 'closed', 'done'])->count();

        $openInvoicesSum = Invoice::whereIn('status', ['sent', 'overdue'])->sum('amount');
        $paidInvoicesSum = Invoice::where('status', 'paid')->sum('amount');

        return [
            Stat::make('Liegenschaften & Einheiten', "{$totalProperties} / {$totalUnits}")
                ->description("{$occupancyRate}% Auslastung ({$occupiedUnits} vermietet)")
                ->descriptionIcon('heroicon-m-building-office-2')
                ->color('primary'),

            Stat::make('Offene Vorgänge / Tickets', $openTickets)
                ->description("{$urgentTickets} mit höchster Dringlichkeit")
                ->descriptionIcon('heroicon-m-wrench-screwdriver')
                ->color($urgentTickets > 0 ? 'danger' : 'warning'),

            Stat::make('Offene Rechnungsbeträge', number_format($openInvoicesSum, 2, ',', '.').' €')
                ->description(number_format($paidInvoicesSum, 2, ',', '.').' € bereits beglichen')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),
        ];
    }
}
