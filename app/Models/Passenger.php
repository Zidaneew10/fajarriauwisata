<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class Passenger extends Model
{
    public const QR_ACTIVE    = 'active';
    public const QR_USED      = 'used';
    public const QR_CANCELLED = 'cancelled';

    protected $fillable = [
        'booking_id',
        'schedule_seat_id',
        'name',
        'gender',
        'phone',
        'qr_code_data',
        'qr_status',
        'scanned_at',
    ];

    protected $hidden = [
        'qr_code_data',
    ];

    protected $casts = [
        'scanned_at' => 'datetime',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function seat(): BelongsTo
    {
        return $this->belongsTo(ScheduleSeat::class, 'schedule_seat_id');
    }

    public function generateQrCode(Booking $booking, string $origin, string $destination): void
    {
        $schedule = $booking->schedule;

        $data = json_encode([
            'booking_code'   => $booking->booking_code,
            'passenger_name' => $this->name,
            'seat'           => $this->seat?->label ?? '-',
            'route'          => "$origin → $destination",
            'departure'      => $schedule->departure_date->format('Y-m-d') . ' ' . $schedule->departure_time,
            'boarding'       => $booking->boardingStop?->name ?? '-',
            'drop'           => $booking->dropStop?->name ?? '-',
            'passenger_id'   => $this->id,
        ]);

        $this->update([
            'qr_code_data' => Crypt::encryptString($data),
            'qr_status'    => self::QR_ACTIVE,
        ]);
    }
}
