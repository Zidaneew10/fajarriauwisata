<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\PromoCode;
use App\Models\PromoCodeUsage;
use App\Models\ScheduleSeat;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookingService
{
    /**
     * $data = ['schedule_bus_id', 'user_id', 'promo_code' (opsional)]
     * $passengers = [['schedule_seat_id', 'name', 'id_number', 'phone'], ...]
     */
    public function create(array $data, array $passengers): Booking
    {
        return DB::transaction(function () use ($data, $passengers) {
            $seatIds = array_column($passengers, 'schedule_seat_id');

            // Pessimistic lock — anti double booking
            $seats = ScheduleSeat::whereIn('id', $seatIds)
                ->where('schedule_bus_id', $data['schedule_bus_id'])
                ->lockForUpdate()
                ->get();

            if ($seats->count() !== count($seatIds)) {
                throw ValidationException::withMessages([
                    'seats' => 'Beberapa seat tidak ditemukan atau bukan milik bus ini.',
                ]);
            }

            $unavailable = $seats->where('is_available', false);
            if ($unavailable->isNotEmpty()) {
                $labels = $unavailable->map(fn($s) => $s->label)->join(', ');
                throw ValidationException::withMessages([
                    'seats' => "Seat sudah tidak tersedia: {$labels}",
                ]);
            }

            // Hitung harga
            $scheduleBus = \App\Models\ScheduleBus::with('busClass')->find($data['schedule_bus_id']);
            $totalPrice  = $scheduleBus->busClass->price * count($seatIds);

            // Apply promo
            $promoCode      = null;
            $discountAmount = 0;

            if (!empty($data['promo_code'])) {
                $promoCode  = PromoCode::where('code', strtoupper($data['promo_code']))->first();

                if (!$promoCode) {
                    throw ValidationException::withMessages(['promo_code' => 'Kode promo tidak ditemukan.']);
                }

                $validation = $promoCode->validate($data['user_id'], $totalPrice);
                if (!$validation['valid']) {
                    throw ValidationException::withMessages(['promo_code' => $validation['message']]);
                }

                $discountAmount = $promoCode->calculateDiscount($totalPrice);
                $totalPrice     = max(0, $totalPrice - $discountAmount);
            }

            // Buat booking
            $booking = Booking::create([
                'schedule_bus_id' => $data['schedule_bus_id'],
                'user_id'         => $data['user_id'] ?? null,
                'promo_code_id'   => $promoCode?->id,
                'discount_amount' => $discountAmount,
                'total_price'     => $totalPrice,
                'status'          => 'pending',
                'expired_at'      => now()->addMinutes(15),
            ]);

            // Catat pemakaian promo
            if ($promoCode) {
                PromoCodeUsage::create([
                    'promo_code_id' => $promoCode->id,
                    'user_id'       => $data['user_id'],
                    'booking_id'    => $booking->id,
                ]);
            }

            // Buat passengers
            foreach ($passengers as $p) {
                $booking->passengers()->create($p);
            }

            // Tandai seat tidak tersedia
            ScheduleSeat::whereIn('id', $seatIds)->update(['is_available' => false]);

            return $booking;
        });
    }

    public function cancel(Booking $booking): void
    {
        if ($booking->status === 'cancelled') return;

        DB::transaction(function () use ($booking) {
            $seatIds = $booking->passengers->pluck('schedule_seat_id')->toArray();
            $booking->update(['status' => 'cancelled']);
            ScheduleSeat::whereIn('id', $seatIds)->update(['is_available' => true]);
            $booking->promoUsage?->delete();
        });
    }
}
