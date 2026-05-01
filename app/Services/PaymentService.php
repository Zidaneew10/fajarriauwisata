<?php

namespace App\Services;
use Illuminate\Support\Facades\Log;
use App\Models\Booking;
use App\Services\BookingService;
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

    public function createSnapToken(Booking $booking): string
    {
        $booking->load([
            'passengers.seat',
            'scheduleBus.busClass',
            'scheduleBus.schedule.busTrip',
            'user',
            'promoCode',
        ]);

        $params = [
            'transaction_details' => [
                'order_id'     => $booking->booking_code,
                'gross_amount' => (int) $booking->total_price,
            ],
            'customer_details' => [
                'first_name' => $booking->user->name ?? 'Guest',
                'email'      => $booking->user->email ?? 'guest@example.com',
            ],
            'item_details' => $this->buildItemDetails($booking),
            'expiry' => [
                'unit'     => 'minutes',
                'duration' => 15,
            ],
        ];

        $snapToken = Snap::getSnapToken($params);
        $booking->update(['midtrans_token' => $snapToken]);

        return $snapToken;
    }

    public function handleWebhook(array $payload): ?Booking
    {
        $signatureKey = hash('sha512',
            $payload['order_id'] .
            $payload['status_code'] .
            $payload['gross_amount'] .
            config('midtrans.server_key')
        );

        if ($signatureKey !== ($payload['signature_key'] ?? '')) {
            Log::warning('Midtrans signature tidak cocok');
            return null;
        }

        $booking = Booking::where('booking_code', $payload['order_id'])->first();
        if (!$booking) return null;

        $transactionStatus = $payload['transaction_status'];
        $fraudStatus       = $payload['fraud_status'] ?? 'accept';

        $newStatus = match(true) {
            $transactionStatus === 'capture' && $fraudStatus === 'accept' => 'paid',
            $transactionStatus === 'settlement'                           => 'paid',
            $transactionStatus === 'pending'                              => 'pending',
            in_array($transactionStatus, ['deny', 'cancel', 'expire'])   => 'cancelled',
            default                                                       => null,
        };

        if ($newStatus && $booking->status !== $newStatus) {
            $booking->update(['status' => $newStatus]);
            if ($newStatus === 'cancelled') {
                app(BookingService::class)->cancel($booking);
            }
        }

        return $booking;
    }

    private function buildItemDetails(Booking $booking): array
    {
        $items        = [];
        $busClass     = $booking->scheduleBus->busClass;
        $pricePerSeat = $busClass->price;

        foreach ($booking->passengers as $passenger) {
            $items[] = [
                'id'       => 'SEAT-' . $passenger->schedule_seat_id,
                'price'    => (int) $pricePerSeat,
                'quantity' => 1,
                'name'     => "Kursi {$passenger->seat->label} - {$busClass->class_type}", // ← fix
            ];
        }

        // Tambah baris diskon jika ada promo
        if ($booking->discount_amount > 0) {
            $items[] = [
                'id'       => 'DISCOUNT',
                'price'    => -(int) $booking->discount_amount,
                'quantity' => 1,
                'name'     => 'Diskon' . ($booking->promoCode ? ' ' . $booking->promoCode->code : ''),
            ];
        }

        return $items;
    }
}
