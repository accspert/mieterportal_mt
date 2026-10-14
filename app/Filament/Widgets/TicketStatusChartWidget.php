<?php

namespace App\Filament\Widgets;

use App\Models\Ticket;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class TicketStatusChartWidget extends ChartWidget
{
    protected static ?int $sort = 2;

    protected ?string $maxHeight = '280px';

    public function getHeading(): ?string
    {
        return 'Ticket-Verteilung nach Status';
    }

    protected function getData(): array
    {
        $counts = Ticket::select('status', DB::raw('count(*) as aggregate'))
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $open = (int) ($counts['open'] ?? 0);
        $inProgress = (int) ($counts['in_progress'] ?? 0);
        $waiting = (int) ($counts['waiting'] ?? 0);
        $resolved = (int) (($counts['resolved'] ?? 0) + ($counts['done'] ?? 0));
        $closed = (int) ($counts['closed'] ?? 0);

        return [
            'datasets' => [
                [
                    'label' => 'Anzahl Tickets',
                    'data' => [$open, $inProgress, $waiting, $resolved, $closed],
                    'backgroundColor' => [
                        '#3b82f6',
                        '#f59e0b',
                        '#8b5cf6',
                        '#10b981',
                        '#64748b',
                    ],
                ],
            ],
            'labels' => ['Neu / Offen', 'In Bearbeitung', 'Wartet auf Rückmeldung', 'Gelöst / Erledigt', 'Geschlossen'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
