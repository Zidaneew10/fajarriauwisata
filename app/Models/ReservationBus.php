<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReservationBus extends Model
{
    protected $fillable = ['reservation_id', 'bus_id', 'class_type', 'price'];

    protected $casts = ['price' => 'decimal:2'];

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function bus(): BelongsTo
    {
        return $this->belongsTo(Bus::class);
    }
}
