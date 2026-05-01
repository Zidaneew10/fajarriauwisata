<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BusClass extends Model
{
    protected $fillable = ['bus_trip_id', 'class_type', 'price', 'capacity'];

    protected $casts = ['price' => 'decimal:2'];

    public function busTrip(): BelongsTo
    {
        return $this->belongsTo(BusTrip::class);
    }

    public function facilities(): BelongsToMany
    {
        return $this->belongsToMany(Facility::class, 'bus_class_facilities');
    }

    public function scheduleBuses(): HasMany
    {
        return $this->hasMany(ScheduleBus::class);
    }
}
