<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\PaymentService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(private PaymentService $paymentService) {}

    /**
     * Generate Snap token untuk booking yang sudah ada.
     * Android pakai token ini untuk buka halaman bayar.
     */
    public function getSnapToken(Request $request, Booking $booking)
    {
        // Pastikan booking milik user ini
        abort_unless($booking->user_id === $request->user()->id, 403);

        if ($booking->status !== 'pending') {
            return response()->json([
                'message' => 'Booking ini tidak bisa dibayar (status: ' . $booking->status . ')',
            ], 422);
        }

        if ($booking->isExpired()) {
            return response()->json(['message' => 'Booking sudah expired'], 422);
        }

        try {
            // CEK DISINI: Jika sudah punya snap_token, gunakan yang lama
            if (!empty($booking->snap_token)) {
                return response()->json([
                    'snap_token' => $booking->snap_token,
                    'snap_url'   => config('midtrans.snap_url'),
                    'booking'    => $booking->fresh(),
                ]);
            }

            // Jika belum ada, generate token baru
            $token = $this->paymentService->createSnapToken($booking);

            // SIMPAN token ke database agar bisa dipakai lagi
            $booking->update([
                'snap_token' => $token
            ]);

            return response()->json([
                'snap_token' => $token,
                'snap_url'   => config('midtrans.snap_url'),
                'booking'    => $booking->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal generate token: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Webhook dari Midtrans — update status booking otomatis.
     * Route ini TIDAK pakai auth middleware (dipanggil Midtrans server).
     */
    public function webhook(Request $request)
    {
        $payload = $request->all();
        $booking = $this->paymentService->handleWebhook($payload);

        if (!$booking) {
            return response()->json(['message' => 'Invalid notification'], 400);
        }

        return response()->json(['message' => 'OK']);
    }
}
