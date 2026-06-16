<?php

namespace App\Models;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduleTemplate extends Model
{
    protected $fillable = [
        'bus_trip_id',
        'departure_times',
        'duration_minutes',
        'days_of_week',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'departure_times' => 'array',
        'days_of_week'    => 'array',
        'start_date'      => 'date',
        'end_date'        => 'date',
    ];

    public function busTrip(): BelongsTo
    {
        return $this->belongsTo(BusTrip::class);
    }

    public function generateSchedules(): int
    {
        $generated = 0;
        $period    = CarbonPeriod::create($this->start_date, $this->end_date);

        foreach ($period as $date) {
            if (!in_array($date->dayOfWeek, $this->days_of_week)) {
                continue;
            }

            foreach ($this->departure_times as $time) {
                $departure = Carbon::parse($date->format('Y-m-d') . ' ' . $time);

                // Hitung arrival otomatis dari duration_minutes
                $arrival = $departure->copy()->addMinutes($this->duration_minutes);

                $exists = Schedule::where('bus_trip_id', $this->bus_trip_id)
                    ->whereDate('departure_date', $date->format('Y-m-d'))
                    ->where('departure_time', $departure->format('H:i:s'))
                    ->exists();

                if ($exists) continue;

                Schedule::create([
                    'bus_trip_id'    => $this->bus_trip_id,
                    'departure_date' => $date->format('Y-m-d'),
                    'departure_time' => $departure->format('H:i:s'),
                    'arrival_time'   => $arrival->format('H:i:s'),
                ]);

                $generated++;
            }
        }

        return $generated;
    }
}
