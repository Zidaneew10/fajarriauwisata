<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reservation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'reservation_code', 'customer_name', 'customer_phone',
        'customer_email', 'destination', 'departure_date',
        'return_date', 'passenger_count', 'notes', 'status',
        'total_price', 'payment_status', 'dp_amount', 'user_id',
    ];

    protected $casts = [
        'departure_date' => 'date',
        'return_date'    => 'date',
        'total_price'    => 'decimal:2',
        'dp_amount'      => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (Reservation $reservation) {
            $reservation->reservation_code = 'RSV-' . strtoupper(uniqid());
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function buses(): HasMany
    {
        return $this->hasMany(ReservationBus::class);
    }

    /**
     * Hitung total harga dari semua bus.
     */
    public function recalculateTotal(): void
    {
        $total = $this->buses->sum('price');
        $this->update(['total_price' => $total]);
    }

    public function getDurationAttribute(): int
    {
        if (!$this->return_date) return 1;
        return $this->departure_date->diffInDays($this->return_date) + 1;
    }
}
