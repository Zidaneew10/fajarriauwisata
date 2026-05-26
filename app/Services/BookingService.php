<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\PromoCode;
use App\Models\PromoCodeUsage;
use App\Models\Schedule;
use App\Models\ScheduleSeat;
use App\Models\Passenger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookingService
{
    public function create(array $data, int $userId): Booking
    {
        return DB::transaction(function () use ($data, $userId) {

            $schedule = Schedule::with('busTrip.routeSegments')->findOrFail($data['schedule_id']);
            $seatIds  = collect($data['passengers'])->pluck('schedule_seat_id')->toArray();

            // Validasi stop ada di rute trip ini
            $routeStopIds = $schedule->busTrip->routeSegments->pluck('stop_id')->toArray();

            if (!in_array($data['boarding_stop_id'], $routeStopIds)) {
                throw ValidationException::withMessages([
                    'boarding_stop_id' => 'Titik naik tidak valid untuk rute ini.',
                ]);
            }

            if (!in_array($data['drop_stop_id'], $routeStopIds)) {
                throw ValidationException::withMessages([
                    'drop_stop_id' => 'Titik turun tidak valid untuk rute ini.',
                ]);
            }

            // Validasi urutan — titik naik harus sebelum titik turun
            $boardingSeq = $schedule->busTrip->routeSegments
                ->firstWhere('stop_id', $data['boarding_stop_id'])?->sequence;
            $dropSeq     = $schedule->busTrip->routeSegments
                ->firstWhere('stop_id', $data['drop_stop_id'])?->sequence;

            if ($boardingSeq >= $dropSeq) {
                throw ValidationException::withMessages([
                    'drop_stop_id' => 'Titik turun harus setelah titik naik.',
                ]);
            }

            // Lock kursi
            $seats = ScheduleSeat::whereIn('id', $seatIds)
                ->where('schedule_id', $schedule->id)
                ->lockForUpdate()
                ->get();

            foreach ($seats as $seat) {
                if (!$seat->is_available) {
                    throw ValidationException::withMessages([
                        'seats' => "Kursi {$seat->label} sudah terisi.",
                    ]);
                }
            }

            // Hitung harga
            $totalPrice = $schedule->busTrip->price * count($seatIds);
            $discount   = 0;
            $promoCode  = null;

            if (!empty($data['promo_code'])) {
                $promoCode = PromoCode::where('code', $data['promo_code'])
                    ->where('is_active', true)
                    ->where('valid_from', '<=', now())
                    ->where('valid_until', '>=', now())
                    ->first();

                if (!$promoCode) {
                    throw ValidationException::withMessages([
                        'promo_code' => 'Kode promo tidak valid.',
                    ]);
                }

                $alreadyUsed = PromoCodeUsage::where('promo_code_id', $promoCode->id)
                    ->where('user_id', $userId)->exists();

                if ($alreadyUsed) {
                    throw ValidationException::withMessages([
                        'promo_code' => 'Kamu sudah pernah menggunakan promo ini.',
                    ]);
                }

                $discount   = $promoCode->calculateDiscount($totalPrice);
                $totalPrice = $totalPrice - $discount;
            }

            $booking = Booking::create([
                'schedule_id'      => $schedule->id,
                'user_id'          => $userId,
                'promo_code_id'    => $promoCode?->id,
                'boarding_stop_id' => $data['boarding_stop_id'],
                'drop_stop_id'     => $data['drop_stop_id'],
                'status'           => 'pending',
                'total_price'      => $totalPrice,
                'discount_amount'  => $discount,
                'expired_at'       => now()->addHours(1),
            ]);

            foreach ($data['passengers'] as $p) {
                Passenger::create([
                    'booking_id'       => $booking->id,
                    'schedule_seat_id' => $p['schedule_seat_id'],
                    'name'             => $p['name'],
                    'gender'           => $p['gender'],
                    'phone'            => $p['phone'] ?? null,
                ]);
            }

            ScheduleSeat::whereIn('id', $seatIds)->update(['is_available' => false]);

            if ($promoCode) {
                $promoCode->increment('used_count');
                PromoCodeUsage::create([
                    'promo_code_id' => $promoCode->id,
                    'user_id'       => $userId,
                    'booking_id'    => $booking->id,
                ]);
            }

            return $booking->load([
                'schedule.busTrip.routeSegments.stop',
                'passengers.seat',
                'boardingStop',
                'dropStop',
            ]);
        });
    }

    public function cancel(Booking $booking): void
    {
        DB::transaction(function () use ($booking) {
            if (!in_array($booking->status, ['pending', 'paid'])) {
                throw ValidationException::withMessages([
                    'status' => 'Booking tidak bisa dibatalkan.',
                ]);
            }

            $seatIds = $booking->passengers->pluck('schedule_seat_id')->toArray();
            ScheduleSeat::whereIn('id', $seatIds)->update(['is_available' => true]);

            if ($booking->promo_code_id) {
                PromoCodeUsage::where('booking_id', $booking->id)->delete();
                PromoCode::find($booking->promo_code_id)?->decrement('used_count');
            }

            $booking->update(['status' => 'cancelled']);
        });
    }
}
