<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use Filament\Widgets\ChartWidget;

class BookingStatusChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Distribusi Status Booking';

    protected static ?int $sort = 3;

    protected static ?string $maxHeight = '280px';

    protected function getData(): array
    {
        $statuses = [
            'pending'   => Booking::where('status', 'pending')->count(),
            'paid'      => Booking::where('status', 'paid')->count(),
            'confirmed' => Booking::where('status', 'confirmed')->count(),
            'cancelled' => Booking::where('status', 'cancelled')->count(),
        ];

        return [
            'datasets' => [
                [
                    'data'            => array_values($statuses),
                    'backgroundColor' => ['#fbbf24', '#3b82f6', '#22c55e', '#ef4444'],
                ],
            ],
            'labels' => ['Pending', 'Lunas', 'Dikonfirmasi', 'Dibatalkan'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
