<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\BookingService;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function __construct(private BookingService $bookingService) {}

    public function store(Request $request)
    {
        $validated = $request->validate([
            'schedule_bus_id'           => 'required|exists:schedule_buses,id',
            'total_price'               => 'nullable|numeric|min:1', // ← TAMBAH
            'passengers'                => 'required|array|min:1',
            'passengers.*.bus_seat_id'  => 'required|exists:bus_seats,id',
            'passengers.*.name'         => 'required|string',
            'passengers.*.id_number'    => 'required|string',
            'passengers.*.phone'        => 'nullable|string',
        ]);

        $booking = $this->bookingService->create(
            [
                'schedule_bus_id' => $validated['schedule_bus_id'],
                'user_id'         => $request->user()->id,
                'total_price'     => $validated['total_price'] ?? null, // ← TAMBAH
            ],
            $validated['passengers']
        );

        return response()->json($booking->load('passengers.seat'), 201);
    }

    public function index(Request $request)
    {
        $bookings = Booking::with(['scheduleBus.schedule.busTrip', 'passengers'])
            ->where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json($bookings);
    }

    public function show(Request $request, Booking $booking)
    {
        abort_unless($booking->user_id === $request->user()->id, 403);

        return response()->json($booking->load(['scheduleBus.schedule.busTrip', 'passengers.seat']));
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
