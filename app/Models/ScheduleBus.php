<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScheduleBus extends Model
{
    protected $fillable = ['schedule_id', 'bus_class_id', 'bus_id'];

    protected static function booted(): void
    {
        static::created(function (ScheduleBus $scheduleBus) {
            $scheduleBus->generateSeats();
        });
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    public function busClass(): BelongsTo
    {
        return $this->belongsTo(BusClass::class);
    }

    public function bus(): BelongsTo
    {
        return $this->belongsTo(Bus::class);
    }

    public function scheduleSeats(): HasMany
    {
        return $this->hasMany(ScheduleSeat::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function generateSeats(): void
    {
        $bus = $this->bus;

        $columns = match ($bus->class_type) {
            'SE 2-1'    => ['A', 'B', 'C'],
            'Sleeper'   => ['A', 'B', 'C', 'D'],
            'Executive' => ['A', 'B', 'C', 'D'],
            default     => ['A', 'B', 'C'],
        };

        $totalRows = (int) ceil($bus->capacity / count($columns));
        $seats     = [];

        for ($row = 1; $row <= $totalRows; $row++) {
            foreach ($columns as $col) {
                $seats[] = [
                    'schedule_bus_id' => $this->id,
                    'row'             => $row,
                    'column'          => $col,
                    'is_available'    => true,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ];
            }
        }

        ScheduleSeat::insert($seats);
    }
}
