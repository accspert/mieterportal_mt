<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class MonthlyRevenueChartWidget extends ChartWidget
{
    protected static ?int $sort = 3;

    protected ?string $maxHeight = '280px';

    public function getHeading(): ?string
    {
        return 'Fakturierte Beträge & Mieteinnahmen';
    }

    protected function getData(): array
    {
        $months = collect(range(5, 0))->map(function ($i) {
            return Carbon::now()->subMonths($i);
        });

        $labels = $months->map(fn ($m) => $m->translatedFormat('M Y'))->toArray();

        $invoicedData = $months->map(function ($month) {
            return (float) Invoice::whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->sum('amount');
        })->toArray();

        $paidData = $months->map(function ($month) {
            return (float) Invoice::whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->where('status', 'paid')
                ->sum('amount');
        })->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'Gesamt fakturiert (€)',
                    'data' => $invoicedData,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.6)',
                    'borderColor' => '#3b82f6',
                ],
                [
                    'label' => 'Erfolgreich bezahlt (€)',
                    'data' => $paidData,
                    'backgroundColor' => 'rgba(16, 185, 129, 0.6)',
                    'borderColor' => '#10b981',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
