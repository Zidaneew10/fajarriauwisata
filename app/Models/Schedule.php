<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Schedule extends Model
{
    protected $fillable = ['bus_trip_id', 'departure_time'];

    protected $casts = ['departure_time' => 'datetime'];

    public function busTrip(): BelongsTo
    {
        return $this->belongsTo(BusTrip::class);
    }

    public function scheduleBuses(): HasMany
    {
        return $this->hasMany(ScheduleBus::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'schedule_bus_id');
    }
}
