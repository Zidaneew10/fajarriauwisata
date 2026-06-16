<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BusTrip extends Model
{
    protected $fillable = [
        'trip_number', 'class_type', 'capacity',
        'price', 'seat_layout', 'description', 'is_active',
    ];

    protected $casts = [
        'price'     => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function routeSegments(): HasMany
    {
        return $this->hasMany(RouteSegment::class)->orderBy('sequence');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    public function busClasses(): HasMany
    {
        return $this->hasMany(BusClass::class);
    }

    public function getSeatColumns(): array
    {
        return match ($this->seat_layout) {
            '2-1'   => ['A', 'B', 'C'],
            '1-1'   => ['A', 'B'],
            default => ['A', 'B', 'C', 'D'],
        };
    }

    public function getTotalRows(): int
    {
        return (int) ceil($this->capacity / count($this->getSeatColumns()));
    }
}
