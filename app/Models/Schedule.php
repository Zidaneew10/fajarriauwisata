<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Schedule extends Model
{
    protected $fillable = ['bus_trip_id', 'departure_date', 'departure_time', 'status'];

    protected $casts = ['departure_date' => 'date'];

    protected static function booted(): void
    {
        // AUTO GENERATE SEATS saat jadwal dibuat
        static::created(function (Schedule $schedule) {
            $schedule->generateSeats();
        });
    }

    public function busTrip(): BelongsTo
    {
        return $this->belongsTo(BusTrip::class);
    }

    public function seats(): HasMany
    {
        return $this->hasMany(ScheduleSeat::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function generateSeats(): void
    {
        $busTrip = $this->busTrip;
        $columns = $busTrip->getSeatColumns();
        $rows    = $busTrip->getTotalRows();
        $seats   = [];

        for ($row = 1; $row <= $rows; $row++) {
            foreach ($columns as $col) {
                $seats[] = [
                    'schedule_id'  => $this->id,
                    'row'          => $row,
                    'column'       => $col,
                    'label'        => $row . $col,
                    'is_available' => true,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ];
            }
        }

        ScheduleSeat::insert($seats);
    }
}
