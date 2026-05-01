<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RouteSegment extends Model
{
    protected $fillable = ['bus_trip_id', 'terminal_id', 'sequence'];

    public function busTrip(): BelongsTo
    {
        return $this->belongsTo(BusTrip::class);
    }

    public function terminal(): BelongsTo
    {
        return $this->belongsTo(Terminal::class);
    }
}
