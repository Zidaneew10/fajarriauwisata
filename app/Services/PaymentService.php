<?php

namespace App\Services;

use App\Models\Booking;
use Midtrans\Config;
use Midtrans\Snap;

class PaymentService
{
    public function __construct()
    {
        Config::$serverKey    = config('midtrans.server_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized  = true;
        Config::$is3ds        = true;
    }

    public function createSnapToken(Booking $booking): array
    {
        $booking->load(['user', 'passengers.seat', 'schedule.busTrip', 'boardingStop', 'dropStop']);

        $orderId = 'FRW-' . $booking->id . '-' . time();

        $params = [
            'transaction_details' => [
                'order_id'     => $orderId,
                'gross_amount' => (int) $booking->total_price,
            ],
            'customer_details' => [
                'first_name' => $booking->user->name,
                'email'      => $booking->user->email,
            ],
            'item_details' => $booking->passengers->map(fn($p) => [
                'id'       => 'SEAT-' . $p->seat->label,
                'price'    => (int) $booking->schedule->busTrip->price,
                'quantity' => 1,
                'name'     => "Kursi {$p->seat->label} - {$booking->schedule->busTrip->trip_number}",
            ])->toArray(),
        ];

        $snapToken = Snap::getSnapToken($params);

        $booking->update([
            'midtrans_token'    => $snapToken,
            'midtrans_order_id' => $orderId,
        ]);

        return [
            'snap_token' => $snapToken,
            'snap_url'   => config('midtrans.snap_url') . $snapToken,
            'booking'    => $booking,
        ];
    }

    public function handleWebhook(array $payload): void
    {
        $orderId = $payload['order_id'] ?? null;
        if (!$orderId) return;

        $booking = Booking::where('midtrans_order_id', $orderId)->first();
        if (!$booking) return;

        $signature = hash('sha512',
            $orderId . $payload['status_code'] . $payload['gross_amount'] . config('midtrans.server_key')
        );

        if ($signature !== $payload['signature_key']) return;

        $status = $payload['transaction_status'];
        $fraud  = $payload['fraud_status'] ?? null;

        if (($status === 'capture' && $fraud === 'accept') || $status === 'settlement') {
            $booking->update(['status' => 'paid']);
        } elseif (in_array($status, ['cancel', 'deny', 'expire'])) {
            app(BookingService::class)->cancel($booking);
        }
    }
}
