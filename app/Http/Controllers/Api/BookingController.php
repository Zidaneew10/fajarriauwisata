<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\PromoCode;
use App\Services\BookingService;
use App\Services\PaymentService;
use DB;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function __construct(
        private BookingService $bookingService,
        private PaymentService $paymentService
    ) {}

    public function index(Request $request)
    {
        $bookings = Booking::with([
            'schedule.busTrip.routeSegments.stop',
            'passengers.seat',
            'boardingStop',
            'dropStop',
        ])
            ->where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json($bookings);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'schedule_id'                   => 'required|exists:schedules,id',
            'boarding_stop_id'              => 'required|exists:stops,id',
            'drop_stop_id'                  => 'required|exists:stops,id',
            'promo_code'                    => 'nullable|string',
            'passengers'                    => 'required|array|min:1',
            'passengers.*.schedule_seat_id' => 'required|exists:schedule_seats,id',
            'passengers.*.name'             => 'required|string',
            'passengers.*.gender'           => 'required|in:Laki-laki,Perempuan',
            'passengers.*.phone'            => 'nullable|string',
        ]);

        $booking = $this->bookingService->create($validated, $request->user()->id);
        return response()->json($booking, 201);
    }

    public function show(Request $request, Booking $booking)
    {
        if ($booking->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $booking->load([
            'schedule.busTrip.routeSegments.stop',
            'passengers.seat',
            'boardingStop',
            'dropStop',
        ]);

        return response()->json($booking);
    }

    public function payment(Request $request, Booking $booking)
    {
        if ($booking->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($booking->status !== 'pending') {
            return response()->json(['message' => 'Booking tidak dalam status pending.'], 400);
        }

        try {
            // 1. CEK: Jika sudah ada snap_token di database, langsung gunakan yang lama
            if (!empty($booking->snap_token)) {
                return response()->json([
                    'snap_token' => $booking->snap_token,
                    'snap_url'   => config('midtrans.snap_url'),
                ]);
            }

            // 2. Jika belum ada, jalankan request ke Midtrans
            $midtransResult = $this->paymentService->createSnapToken($booking);

            // 3. ANTISIPASI: Pastikan kita hanya mengambil string token-nya saja
            // Jika createSnapToken mengembalikan array/object, ekstrak string-nya
            $token = is_array($midtransResult)
                ? ($midtransResult['snap_token'] ?? $midtransResult['token'] ?? null)
                : $midtransResult;

            if (is_object($token)) {
                $token = $token->token ?? null;
            }

            if (empty($token) || !is_string($token)) {
                throw new \Exception('Hasil dari PaymentService bukan string token yang valid. Tipe: ' . gettype($midtransResult));
            }

            // 4. SIMPAN menggunakan DB Fluent (Bypass Eloquent Event untuk mencegah loop rekursif)
            DB::table('bookings')->where('id', $booking->id)->update([
                'snap_token' => $token
            ]);

            // 5. Kembalikan response bersih tanpa menyertakan object $booking yang rawan circular
            return response()->json([
                'snap_token' => $token,
                'snap_url'   => config('midtrans.snap_url'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal generate token: ' . $e->getMessage()
            ], 500);
        }
    }

    public function cancel(Request $request, Booking $booking)
    {
        if ($booking->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $this->bookingService->cancel($booking);
        return response()->json(['message' => 'Booking berhasil dibatalkan.']);
    }

    public function checkPromo(Request $request)
    {
        $request->validate([
            'code'        => 'required|string',
            'total_price' => 'required|numeric',
        ]);

        $promo = PromoCode::where('code', $request->code)
            ->where('is_active', true)
            ->where('valid_from', '<=', now())
            ->where('valid_until', '>=', now())
            ->first();

        if (!$promo) {
            return response()->json(['valid' => false, 'message' => 'Kode promo tidak valid.']);
        }

        if ($promo->usage_limit && $promo->used_count >= $promo->usage_limit) {
            return response()->json(['valid' => false, 'message' => 'Kode promo sudah habis.']);
        }

        $alreadyUsed = $promo->usages()->where('user_id', $request->user()->id)->exists();
        if ($alreadyUsed) {
            return response()->json(['valid' => false, 'message' => 'Promo sudah pernah digunakan.']);
        }

        if ($request->total_price < $promo->min_purchase) {
            return response()->json(['valid' => false, 'message' => 'Minimum pembelian tidak terpenuhi.']);
        }

        $discount   = $promo->calculateDiscount($request->total_price);
        $finalPrice = $request->total_price - $discount;

        return response()->json([
            'valid'           => true,
            'message'         => 'Promo berhasil dipakai!',
            'promo_code'      => $promo->code,
            'discount_type'   => $promo->discount_type,
            'discount_value'  => $promo->discount_value,
            'discount_amount' => $discount,
            'original_price'  => $request->total_price,
            'final_price'     => $finalPrice,
        ]);
    }
}
