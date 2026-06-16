<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use App\Models\Reservation;
use App\Models\Schedule;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class BookingStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $todayBookings = Booking::whereDate('created_at', today())->count();
        $paidBookings  = Booking::whereIn('status', ['paid', 'confirmed'])->count();
        $activeSchedules = Schedule::where('status', Schedule::STATUS_ACTIVE)
            ->bookable()
            ->count();
        $pendingReservations = Reservation::where('status', 'pending')->count();

        $monthlyRevenue = Booking::whereIn('status', ['paid', 'confirmed'])
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('total_price');

        return [
            Stat::make('Booking Hari Ini', $todayBookings)
                ->description('Tiket baru hari ini')
                ->descriptionIcon('heroicon-m-ticket')
                ->color('primary'),

            Stat::make('Tiket Lunas', $paidBookings)
                ->description('Paid & dikonfirmasi')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success'),

            Stat::make('Jadwal Aktif', $activeSchedules)
                ->description('Belum lewat waktu')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('info'),

            Stat::make('Pendapatan Bulan Ini', 'Rp ' . number_format($monthlyRevenue, 0, ',', '.'))
                ->description('Dari booking tiket')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('warning'),

            Stat::make('Reservasi Pending', $pendingReservations)
                ->description('Menunggu konfirmasi')
                ->descriptionIcon('heroicon-m-clock')
                ->color('gray'),
        ];
    }
}
