<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Booking extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'booking_code', 'schedule_bus_id', 'user_id', 'promo_code_id',
        'status', 'total_price', 'discount_amount',
        'payment_method', 'midtrans_token', 'expired_at',
    ];

    protected $casts = [
        'expired_at'      => 'datetime',
        'total_price'     => 'decimal:2',
        'discount_amount' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (Booking $booking) {
            $booking->booking_code = 'BK-' . strtoupper(uniqid());
        });
    }

    public function scheduleBus(): BelongsTo
    {
        return $this->belongsTo(ScheduleBus::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function promoCode(): BelongsTo
    {
        return $this->belongsTo(PromoCode::class);
    }

    public function promoUsage(): HasOne
    {
        return $this->hasOne(PromoCodeUsage::class);
    }

    public function passengers(): HasMany
    {
        return $this->hasMany(Passenger::class);
    }

    public function isExpired(): bool
    {
        return $this->expired_at && $this->expired_at->isPast();
    }
}
