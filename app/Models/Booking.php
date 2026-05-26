<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Booking extends Model
{
    protected $fillable = [
        'booking_code',
        'schedule_id',
        'user_id',
        'promo_code_id',
        'boarding_stop_id',
        'drop_stop_id',
        'status',
        'snap_token',
        'total_price',
        'discount_amount',
        'midtrans_token',
        'midtrans_order_id',
        'expired_at',
    ];

    protected $casts = [
        'total_price'     => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'expired_at'      => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Booking $booking) {
            $booking->booking_code = 'BK-' . strtoupper(uniqid());
        });
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    public function promoCode(): BelongsTo
    {
        return $this->belongsTo(PromoCode::class);
    }
    public function boardingStop(): BelongsTo
    {
        return $this->belongsTo(Stop::class, 'boarding_stop_id');
    }
    public function dropStop(): BelongsTo
    {
        return $this->belongsTo(Stop::class, 'drop_stop_id');
    }
    public function passengers(): HasMany
    {
        return $this->hasMany(Passenger::class);
    }
}
