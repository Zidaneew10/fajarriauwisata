<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\PromoCode;
use App\Services\BookingService;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function __construct(private BookingService $bookingService) {}

    public function store(Request $request)
    {
        $validated = $request->validate([
            'schedule_bus_id'                   => 'required|exists:schedule_buses,id',
            'promo_code'                        => 'nullable|string',
            'passengers'                        => 'required|array|min:1',
            'passengers.*.schedule_seat_id'     => 'required|exists:schedule_seats,id',
            'passengers.*.name'                 => 'required|string',
            'passengers.*.id_number'            => 'required|string',
            'passengers.*.phone'                => 'nullable|string',
        ]);

        $booking = $this->bookingService->create(
            [
                'schedule_bus_id' => $validated['schedule_bus_id'],
                'user_id'         => $request->user()->id,
                'promo_code'      => $validated['promo_code'] ?? null,
            ],
            $validated['passengers']
        );

        return response()->json($booking->load('passengers.seat'), 201);
    }

    public function checkPromo(Request $request)
    {
        $request->validate([
            'code'        => 'required|string',
            'total_price' => 'required|numeric|min:1',
        ]);

        $promo = PromoCode::where('code', strtoupper($request->code))->first();

        if (!$promo) {
            return response()->json(['valid' => false, 'message' => 'Kode promo tidak ditemukan.'], 404);
        }

        $validation = $promo->validate($request->user()->id, $request->total_price);
        if (!$validation['valid']) {
            return response()->json(['valid' => false, 'message' => $validation['message']], 422);
        }

        $discount   = $promo->calculateDiscount($request->total_price);
        $finalPrice = max(0, $request->total_price - $discount);

        return response()->json([
            'valid'           => true,
            'promo_code'      => $promo->code,
            'discount_type'   => $promo->discount_type,
            'discount_value'  => $promo->discount_value,
            'discount_amount' => $discount,
            'original_price'  => $request->total_price,
            'final_price'     => $finalPrice,
        ]);
    }

    public function index(Request $request)
    {
        $bookings = Booking::with(['scheduleBus.busClass', 'scheduleBus.schedule.busTrip', 'passengers'])
            ->where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json($bookings);
    }

    public function show(Request $request, Booking $booking)
    {
        abort_unless($booking->user_id === $request->user()->id, 403);
        return response()->json($booking->load([
            'scheduleBus.busClass', 'scheduleBus.schedule.busTrip', 'passengers.seat', 'promoCode'
        ]));
    }

    public function cancel(Request $request, Booking $booking)
    {
        abort_unless($booking->user_id === $request->user()->id, 403);

        if ($booking->status === 'paid') {
            return response()->json(['message' => 'Booking yang sudah dibayar tidak bisa dibatalkan'], 422);
        }

        $this->bookingService->cancel($booking);
        return response()->json(['message' => 'Booking berhasil dibatalkan']);
    }
}
