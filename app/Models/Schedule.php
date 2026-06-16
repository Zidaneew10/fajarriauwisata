<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Schedule extends Model
{
    public const STATUS_ACTIVE    = 'active';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_COMPLETED = 'completed';

    protected $fillable = ['bus_trip_id', 'departure_date', 'departure_time', 'arrival_time', 'status'];

    protected $casts = ['departure_date' => 'date'];

    protected static function booted(): void
    {
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

    public function departureDateTime(): Carbon
    {
        $time = substr((string) $this->departure_time, 0, 8);

        return Carbon::parse($this->departure_date->format('Y-m-d') . ' ' . $time);
    }

    public function isPast(): bool
    {
        return $this->departureDateTime()->isPast();
    }

    public function isBookable(): bool
    {
        return $this->status === self::STATUS_ACTIVE && now()->addMinutes(10)->lt($this->departureDateTime());
    }

    public function scopeBookable(Builder $query): Builder
    {
        $cutoffTime = now()->addMinutes(10);

        return $query
            ->where('status', self::STATUS_ACTIVE)
            ->where(function (Builder $q) use ($cutoffTime) {
                $q->whereDate('departure_date', '>', $cutoffTime->toDateString())
                    ->orWhere(function (Builder $q2) use ($cutoffTime) {
                        $q2->whereDate('departure_date', $cutoffTime->toDateString())
                            ->whereTime('departure_time', '>=', $cutoffTime->format('H:i:s'));
                    });
            });
    }

    public static function markPastSchedulesAsCompleted(): int
    {
        $cutoffTime = now()->addMinutes(10);

        return static::query()
            ->where('status', self::STATUS_ACTIVE)
            ->where(function (Builder $q) use ($cutoffTime) {
                $q->whereDate('departure_date', '<', $cutoffTime->toDateString())
                    ->orWhere(function (Builder $q2) use ($cutoffTime) {
                        $q2->whereDate('departure_date', $cutoffTime->toDateString())
                            ->whereTime('departure_time', '<', $cutoffTime->format('H:i:s'));
                    });
            })
            ->update(['status' => self::STATUS_COMPLETED]);
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
