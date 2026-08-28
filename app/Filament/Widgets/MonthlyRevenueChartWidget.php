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
            return Carbon::now()->subMonths($i)->startOfMonth();
        });

        $labels = $months->map(fn ($m) => $m->translatedFormat('M Y'))->toArray();
        $startDate = $months->first()->copy()->startOfMonth();

        // Single query for entire 6 month period
        $invoices = Invoice::where('created_at', '>=', $startDate)
            ->select('created_at', 'status', 'amount')
            ->get();

        $invoicedData = [];
        $paidData = [];

        foreach ($months as $month) {
            $year = $month->year;
            $m = $month->month;

            $monthInvoices = $invoices->filter(function ($inv) use ($year, $m) {
                $c = Carbon::parse($inv->created_at);
                return $c->year === $year && $c->month === $m;
            });

            $invoicedData[] = (float) $monthInvoices->sum('amount');
            $paidData[] = (float) $monthInvoices->where('status', 'paid')->sum('amount');
        }

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
