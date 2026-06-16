<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\BookingStatsWidget;
use App\Filament\Widgets\BookingStatusChartWidget;
use App\Filament\Widgets\BookingTrendChartWidget;
use App\Filament\Widgets\RevenueChartWidget;
use App\Filament\Widgets\StockAlertWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    public function getWidgets(): array
    {
        return [
            BookingStatsWidget::class,
            BookingTrendChartWidget::class,
            BookingStatusChartWidget::class,
            RevenueChartWidget::class,
            StockAlertWidget::class,
        ];
    }

    public function getColumns(): int | string | array
    {
        return [
            'default' => 1,
            'md'      => 2,
            'xl'      => 2,
        ];
    }
}
