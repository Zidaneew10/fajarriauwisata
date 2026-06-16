<?php

namespace App\Filament\Widgets;

use App\Models\Bus;
use App\Models\Reservation;
use Carbon\Carbon;
use Filament\Widgets\Widget;

class BusAvailabilityWidget extends Widget
{
    protected static string $view = 'filament.widgets.bus-availability-widget';

    protected static ?int $sort = 10;

    protected int | string | array $columnSpan = 'full';

    public $currentMonth;
    public $currentYear;

    public function mount()
    {
        $this->currentMonth = now()->month;
        $this->currentYear = now()->year;
    }

    public function previousMonth()
    {
        $date = Carbon::create($this->currentYear, $this->currentMonth, 1)->subMonth();
        $this->currentMonth = $date->month;
        $this->currentYear = $date->year;
    }

    public function nextMonth()
    {
        $date = Carbon::create($this->currentYear, $this->currentMonth, 1)->addMonth();
        $this->currentMonth = $date->month;
        $this->currentYear = $date->year;
    }

    public function getChartDataProperty()
    {
        $monthDate = Carbon::create($this->currentYear, $this->currentMonth, 1)->startOfMonth();
        $daysInMonth = $monthDate->daysInMonth;
        
        $buses = Bus::orderBy('plate_number')->get();
        
        $reservations = Reservation::with('buses.bus')
            ->whereIn('status', ['confirmed', 'in_progress', 'completed', 'pending'])
            ->whereDate('departure_date', '<=', $monthDate->copy()->endOfMonth())
            ->where(function($q) use ($monthDate) {
                $q->whereDate('return_date', '>=', $monthDate)
                  ->orWhereNull('return_date');
            })
            ->get();

        $chartData = [];
        
        foreach ($buses as $bus) {
            $chartData[$bus->id] = [
                'bus' => $bus,
                'schedule' => array_fill(1, $daysInMonth, null)
            ];
        }

        foreach ($reservations as $reservation) {
            $color = match ($reservation->payment_status) {
                'unpaid' => '#ef4444',
                'dp' => '#eab308',
                'paid' => '#22c55e',
                default => '#6b7280',
            };
            
            $resStart = Carbon::parse($reservation->departure_date)->startOfDay();
            $resEnd = $reservation->return_date ? Carbon::parse($reservation->return_date)->startOfDay() : $resStart->copy();
            
            foreach ($reservation->buses as $resBus) {
                $busId = $resBus->bus_id;
                if (isset($chartData[$busId])) {
                    for ($day = 1; $day <= $daysInMonth; $day++) {
                        $currentDate = $monthDate->copy()->day($day);
                        if ($currentDate->between($resStart, $resEnd)) {
                            $isStart = $currentDate->isSameDay($resStart) || $day === 1;
                            $isEnd = $currentDate->isSameDay($resEnd) || $day === $daysInMonth;
                            
                            $chartData[$busId]['schedule'][$day] = [
                                'reservation' => $reservation,
                                'color' => $color,
                                'isStart' => $isStart,
                                'isEnd' => $isEnd,
                            ];
                        }
                    }
                }
            }
        }
        
        return [
            'monthDate' => $monthDate,
            'daysInMonth' => $daysInMonth,
            'data' => $chartData
        ];
    }
}
