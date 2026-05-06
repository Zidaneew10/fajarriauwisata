<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bus extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'bus_code', 'plate_number', 'class_type',
        'capacity', 'brand', 'model', 'year', 'status',
    ];

    public function scheduleBuses(): HasMany
    {
        return $this->hasMany(ScheduleBus::class);
    }

    public function sparePartOuts(): HasMany
    {
        return $this->hasMany(SparePartOut::class);
    }

    public function reservationBuses(): HasMany
    {
        return $this->hasMany(ReservationBus::class);
    }

    public function getLabelAttribute(): string
    {
        return "{$this->plate_number} — {$this->class_type} ({$this->bus_code})";
    }
}
