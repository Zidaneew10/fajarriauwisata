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
        $booking->load([
            'user',
            'passengers.seat',
            'schedule.busTrip',
            'boardingStop',
            'dropStop'
        ]);

        // unique order id (lebih aman dari duplicate)
        $orderId = 'FRW-' . $booking->id . '-' . uniqid();

        $itemDetails = [];

        foreach ($booking->passengers as $passenger) {
            $itemDetails[] = [
                'id'       => 'SEAT-' . $passenger->seat->label,
                'price'    => (int) $booking->schedule->busTrip->price,
                'quantity' => 1,
                'name'     => 'Kursi ' . $passenger->seat->label,
            ];
        }

        if ($booking->discount_amount > 0) {
            $itemDetails[] = [
                'id'       => 'PROMO',
                'price'    => -(int) $booking->discount_amount,
                'quantity' => 1,
                'name'     => 'Diskon Promo',
            ];
        }

        $params = [
            'transaction_details' => [
                'order_id'     => $orderId,
                'gross_amount' => (int) $booking->total_price,
            ],
            'customer_details' => [
                'first_name' => $booking->user->name,
                'email'      => $booking->user->email,
            ],
            'item_details' => $itemDetails,
        ];

        $snapToken = Snap::getSnapToken($params);

        $booking->update([
            'midtrans_token'    => $snapToken,
            'midtrans_order_id' => $orderId,
        ]);

        return [
            'snap_token' => $snapToken,
            'snap_url'   => config('midtrans.snap_url'),
        ];
    }

    public function handleWebhook(array $payload): void
    {
        $orderId = $payload['order_id'] ?? null;
        if (!$orderId) return;

        $booking = Booking::where('midtrans_order_id', $orderId)->first();
        if (!$booking) return;

        // verify signature
        $signature = hash('sha512',
            $orderId .
            $payload['status_code'] .
            $payload['gross_amount'] .
            config('midtrans.server_key')
        );

        if ($signature !== $payload['signature_key']) return;

        $status = $payload['transaction_status'];
        $fraud  = $payload['fraud_status'] ?? null;


         //PAYMENT SUCCESS

        if (($status === 'capture' && $fraud === 'accept') || $status === 'settlement') {

            if ($booking->status !== Booking::STATUS_PAID) {

                $booking->update([
                    'status' => Booking::STATUS_PAID
                ]);

                // 🔥 FIX PENTING: QR TIDAK DI BLOCKING
                dispatch(function () use ($booking) {
                    $booking->generateAllQrCodes();
                });
            }
        }


         //PAYMENT FAILED / EXPIRED

        elseif (in_array($status, ['cancel', 'deny', 'expire'])) {

            app(\App\Services\BookingService::class)->cancel($booking);
        }
    }
}
