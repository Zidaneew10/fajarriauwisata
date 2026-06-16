<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class BookingTrendChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Booking 7 Hari Terakhir';

    protected static ?int $sort = 2;

    protected static ?string $maxHeight = '280px';

    protected function getData(): array
    {
        $labels = [];
        $counts = [];

        for ($i = 6; $i >= 0; $i--) {
            $date     = Carbon::today()->subDays($i);
            $labels[] = $date->translatedFormat('d M');
            $counts[] = Booking::whereDate('created_at', $date)->count();
        }

        return [
            'datasets' => [
                [
                    'label'           => 'Jumlah Booking',
                    'data'            => $counts,
                    'borderColor'     => '#f59e0b',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.15)',
                    'fill'            => true,
                    'tension'         => 0.3,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
