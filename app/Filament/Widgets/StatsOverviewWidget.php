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

        $unitStats = Unit::selectRaw('COUNT(*) as total, COUNT(current_renter_id) as occupied')->first();
        $totalUnits = (int) ($unitStats->total ?? 0);
        $occupiedUnits = (int) ($unitStats->occupied ?? 0);
        $occupancyRate = $totalUnits > 0 ? round(($occupiedUnits / $totalUnits) * 100, 1) : 0;

        $ticketStats = Ticket::selectRaw("
            SUM(CASE WHEN status IN ('open', 'in_progress', 'waiting') THEN 1 ELSE 0 END) as open_count,
            SUM(CASE WHEN priority = 'urgent' AND status NOT IN ('resolved', 'closed', 'done') THEN 1 ELSE 0 END) as urgent_count
        ")->first();

        $openTickets = (int) ($ticketStats->open_count ?? 0);
        $urgentTickets = (int) ($ticketStats->urgent_count ?? 0);

        $invoiceStats = Invoice::selectRaw("
            SUM(CASE WHEN status IN ('sent', 'overdue') THEN amount ELSE 0 END) as open_amount,
            SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END) as paid_amount
        ")->first();

        $openInvoicesSum = (float) ($invoiceStats->open_amount ?? 0);
        $paidInvoicesSum = (float) ($invoiceStats->paid_amount ?? 0);

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
