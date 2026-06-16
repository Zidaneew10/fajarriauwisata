<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\PromoCode;
use App\Services\BookingService;
use App\Services\PaymentService;
use App\Services\QrCodeService;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BookingController extends Controller
{
    public function __construct(
        private BookingService $bookingService,
        private PaymentService $paymentService,
        private QrCodeService $qrCodeService,
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
            ->paginate(15);

        $bookings->getCollection()->transform(function ($booking) {
            return $booking->prepareForApi();
        });

        return response()->json($bookings);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'schedule_id'                   => 'required|exists:schedules,id',
            'boarding_stop_id'              => 'required|exists:stops,id|different:drop_stop_id',
            'drop_stop_id'                  => 'required|exists:stops,id',
            'promo_code'                    => 'nullable|string',
            'passengers'                    => 'required|array|min:1',
            'passengers.*.schedule_seat_id' => 'required|exists:schedule_seats,id',
            'passengers.*.name'             => 'required|string',
            'passengers.*.gender'           => 'required|in:Laki-laki,Perempuan',
            'passengers.*.phone'            => 'nullable|string',
        ]);

        $booking = $this->bookingService->create(
            $validated,
            $request->user()->id
        );

        return response()->json(
            $booking->prepareForApi(),
            201
        );
    }

    public function show(Request $request, Booking $booking)
    {
        abort_if(
            $booking->user_id !== $request->user()->id,
            403,
            'Unauthorized'
        );

        $booking->loadMissing([
            'schedule.busTrip.routeSegments.stop',
            'passengers.seat',
            'boardingStop',
            'dropStop',
        ]);

        return response()->json(
            $booking->prepareForApi()
        );
    }

    public function payment(Request $request, Booking $booking)
    {
        abort_if(
            $booking->user_id !== $request->user()->id,
            403,
            'Unauthorized'
        );

        if ($booking->status !== 'pending') {
            return response()->json([
                'message' => 'Booking tidak dalam status pending.'
            ], 400);
        }

        try {

            if (!empty($booking->snap_token)) {
                return response()->json([
                    'snap_token' => $booking->snap_token,
                    'snap_url'   => config('midtrans.snap_url'),
                ]);
            }

            $midtransResult = $this->paymentService->createSnapToken($booking);

            $token = $this->extractSnapToken($midtransResult);

            if (empty($token) || !is_string($token)) {
                throw new \Exception('Snap token tidak valid.');
            }

            DB::table('bookings')
                ->where('id', $booking->id)
                ->update([
                    'snap_token' => $token,
                    'updated_at' => now(),
                ]);

            return response()->json([
                'snap_token' => $token,
                'snap_url'   => config('midtrans.snap_url'),
            ]);

        } catch (\Exception $e) {

            Log::error('Generate Midtrans token gagal', [
                'booking_id' => $booking->id,
                'error'      => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Gagal generate token pembayaran.'
            ], 500);
        }
    }

    public function cancel(Request $request, Booking $booking)
    {
        abort_if(
            $booking->user_id !== $request->user()->id,
            403,
            'Unauthorized'
        );

        $this->bookingService->cancel($booking);

        return response()->json([
            'message' => 'Booking berhasil dibatalkan.'
        ]);
    }

    public function checkPromo(Request $request)
    {
        $request->validate([
            'code'        => 'required|string',
            'total_price' => 'required|numeric',
        ]);

        $now = now();

        $promo = PromoCode::query()
            ->where('code', $request->code)
            ->where('is_active', true)
            ->where('valid_from', '<=', $now)
            ->where('valid_until', '>=', $now)
            ->first();

        if (!$promo) {
            return response()->json([
                'valid'   => false,
                'message' => 'Kode promo tidak valid.'
            ]);
        }

        if ($promo->usage_limit && $promo->used_count >= $promo->usage_limit) {
            return response()->json([
                'valid'   => false,
                'message' => 'Kode promo sudah habis.'
            ]);
        }

        $alreadyUsed = $promo->usages()
            ->where('user_id', $request->user()->id)
            ->exists();

        if ($alreadyUsed) {
            return response()->json([
                'valid'   => false,
                'message' => 'Promo sudah pernah digunakan.'
            ]);
        }

        if ($request->total_price < $promo->min_purchase) {
            return response()->json([
                'valid'   => false,
                'message' => 'Minimum pembelian tidak terpenuhi.'
            ]);
        }

        $discount = $promo->calculateDiscount($request->total_price);

        return response()->json([
            'valid'           => true,
            'message'         => 'Promo berhasil dipakai!',
            'promo_code'      => $promo->code,
            'discount_type'   => $promo->discount_type,
            'discount_value'  => $promo->discount_value,
            'discount_amount' => $discount,
            'original_price'  => $request->total_price,
            'final_price'     => $request->total_price - $discount,
        ]);
    }

    public function tickets(Request $request, string $booking_code)
    {
        $booking = Booking::with([
            'schedule.busTrip.routeSegments.stop',
            'passengers.seat',
            'boardingStop',
            'dropStop',
        ])
            ->where('booking_code', $booking_code)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$booking) {
            return response()->json([
                'message' => 'Booking tidak ditemukan.'
            ], 404);
        }

        if (!in_array(
            $booking->status,
            [Booking::STATUS_PAID, Booking::STATUS_CONFIRMED],
            true
        )) {
            return response()->json([
                'message' => 'Booking belum dibayar.'
            ], 400);
        }

        if ($booking->passengers->whereNull('qr_code_data')->isNotEmpty()) {
            $booking->generateAllQrCodes();
            $booking->load('passengers.seat');
        }

        $segments = $booking->schedule->busTrip->routeSegments
            ->sortBy('sequence');

        $origin = $segments->first()?->stop?->city ?? '-';
        $destination = $segments->last()?->stop?->city ?? '-';

        $tickets = [];

        foreach ($booking->passengers as $passenger) {

            $svgContent = $this->qrCodeService
                ->generateTicketQrSvg($passenger->qr_code_data);

            $tickets[] = [
                'passenger_name' => $passenger->name,
                'seat'           => $passenger->seat?->label ?? '-',
                'booking_code'   => $booking->booking_code,
                'origin'         => $origin,
                'destination'    => $destination,
                'departure_date' => $booking->schedule->departure_date->format('Y-m-d'),
                'departure_time' => $booking->schedule->departure_time,
                'boarding_stop'  => $booking->boardingStop?->name ?? '-',
                'drop_stop'      => $booking->dropStop?->name ?? '-',
                'qr_status'      => $passenger->qr_status,
                'qr_code_svg'    => base64_encode($svgContent),
            ];
        }

        return response()->json([
            'data' => $tickets
        ]);
    }

    private function extractSnapToken($result): ?string
    {
        if (is_string($result)) {
            return $result;
        }

        if (is_array($result)) {
            return $result['snap_token']
                ?? $result['token']
                ?? null;
        }

        if (is_object($result)) {
            return $result->token ?? null;
        }

        return null;
    }
}
