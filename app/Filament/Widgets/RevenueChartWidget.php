<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class RevenueChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Pendapatan Tiket (6 Bulan Terakhir)';

    protected static ?int $sort = 4;

    protected static ?string $maxHeight = '280px';

    protected function getData(): array
    {
        $labels  = [];
        $revenue = [];

        for ($i = 5; $i >= 0; $i--) {
            $date      = Carbon::now()->subMonths($i);
            $labels[]  = $date->translatedFormat('M Y');
            $revenue[] = (int) Booking::whereIn('status', ['paid', 'confirmed'])
                ->whereMonth('created_at', $date->month)
                ->whereYear('created_at', $date->year)
                ->sum('total_price');
        }

        return [
            'datasets' => [
                [
                    'label'           => 'Pendapatan (Rp)',
                    'data'            => $revenue,
                    'backgroundColor' => 'rgba(34, 197, 94, 0.7)',
                    'borderColor'     => '#22c55e',
                    'borderWidth'     => 1,
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
