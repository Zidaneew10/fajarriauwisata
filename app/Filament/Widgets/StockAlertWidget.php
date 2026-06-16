<?php

namespace App\Filament\Widgets;

use App\Models\SparePart;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StockAlertWidget extends BaseWidget
{
    protected static ?int $sort = 5;

    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $critical = SparePart::whereRaw('stock <= safety_stock')->count();
        $reorder  = SparePart::whereRaw('stock <= rop AND stock > safety_stock')->count();
        $total    = SparePart::count();

        return [
            Stat::make('Total Sparepart', $total)
                ->icon('heroicon-o-wrench-screwdriver'),

            Stat::make('Perlu Reorder', $reorder)
                ->color('warning')
                ->icon('heroicon-o-exclamation-triangle')
                ->description('Stok di bawah ROP'),

            Stat::make('Stok Kritis', $critical)
                ->color('danger')
                ->icon('heroicon-o-x-circle')
                ->description('Stok di bawah Safety Stock'),
        ];
    }
}
