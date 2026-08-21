<?php

namespace App\Filament\Widgets;

use App\Models\Ticket;
use Filament\Widgets\ChartWidget;

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
        $open = Ticket::where('status', 'open')->count();
        $inProgress = Ticket::where('status', 'in_progress')->count();
        $waiting = Ticket::where('status', 'waiting')->count();
        $resolved = Ticket::whereIn('status', ['resolved', 'done'])->count();
        $closed = Ticket::where('status', 'closed')->count();

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
