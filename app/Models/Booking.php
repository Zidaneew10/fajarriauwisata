<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Booking extends Model
{

    public const STATUS_PENDING   = 'pending';
    public const STATUS_PAID      = 'paid';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_EXPIRED   = 'expired';

    protected $fillable = [
        'schedule_id',
        'user_id',
        'promo_code_id',
        'boarding_stop_id',
        'drop_stop_id',
        'status',
        'total_price',
        'discount_amount',
        'snap_token',
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

            do {
                $bookingCode = 'BK-' .
                    now()->format('Ymd') . '-' .
                    strtoupper(Str::random(6));

            } while (
                Booking::where('booking_code', $bookingCode)->exists()
            );

            $booking->booking_code = $bookingCode;

            if (empty($booking->status)) {
                $booking->status = self::STATUS_PENDING;
            }
        });
    }

    public function generateAllQrCodes(): void
    {
        $this->load([
            'passengers.seat',
            'schedule.busTrip.routeSegments.stop',
            'boardingStop',
            'dropStop',
        ]);

        $segments = $this->schedule->busTrip->routeSegments->sortBy('sequence');
        $origin      = $segments->first()?->stop?->city ?? '-';
        $destination = $segments->last()?->stop?->city ?? '-';

        foreach ($this->passengers as $passenger) {
            $passenger->generateQrCode($this, $origin, $destination);
        }
    }

    public function prepareForApi(): self
    {
        if (in_array($this->status, [self::STATUS_PAID, self::STATUS_CONFIRMED], true)) {
            $this->passengers->each->makeVisible('qr_code_data');
        }

        return $this;
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
        return $this->belongsTo(
            Stop::class,
            'boarding_stop_id'
        );
    }

    public function dropStop(): BelongsTo
    {
        return $this->belongsTo(
            Stop::class,
            'drop_stop_id'
        );
    }

    public function passengers(): HasMany
    {
        return $this->hasMany(Passenger::class);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isExpired(): bool
    {
        return $this->status === self::STATUS_EXPIRED;
    }
}
