<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Passenger;
use App\Models\Schedule;
use App\Models\ScheduleSeat;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RescheduleService
{
    /**
     * Pindahkan booking ke jadwal baru.
     * Kursi dicocokkan berdasarkan label yang sama di jadwal baru.
     */
    public function reschedule(Booking $booking, int $newScheduleId): Booking
    {
        if (!in_array($booking->status, [Booking::STATUS_PAID, Booking::STATUS_CONFIRMED], true)) {
            throw ValidationException::withMessages([
                'schedule_id' => 'Hanya booking lunas atau dikonfirmasi yang bisa diganti jadwal.',
            ]);
        }

        return DB::transaction(function () use ($booking, $newScheduleId) {
            $booking->load(['passengers.seat', 'schedule']);

            $newSchedule = Schedule::with('busTrip')->lockForUpdate()->findOrFail($newScheduleId);

            if (!$newSchedule->isBookable()) {
                throw ValidationException::withMessages([
                    'schedule_id' => 'Jadwal tujuan tidak aktif atau sudah lewat.',
                ]);
            }

            if ($booking->schedule_id === $newSchedule->id) {
                throw ValidationException::withMessages([
                    'schedule_id' => 'Booking sudah menggunakan jadwal ini.',
                ]);
            }

            $oldSeatIds = $booking->passengers->pluck('schedule_seat_id')->toArray();
            $seatLabels = $booking->passengers->mapWithKeys(fn ($p) => [
                $p->id => $p->seat?->label,
            ]);

            $newSeats = ScheduleSeat::where('schedule_id', $newSchedule->id)
                ->whereIn('label', $seatLabels->filter()->values())
                ->lockForUpdate()
                ->get()
                ->keyBy('label');

            foreach ($booking->passengers as $passenger) {
                $label = $seatLabels[$passenger->id] ?? null;

                if (!$label || !$newSeats->has($label)) {
                    throw ValidationException::withMessages([
                        'schedule_id' => "Kursi {$label} tidak tersedia di jadwal baru.",
                    ]);
                }

                $newSeat = $newSeats->get($label);

                if (!$newSeat->is_available) {
                    throw ValidationException::withMessages([
                        'schedule_id' => "Kursi {$label} sudah terisi di jadwal baru.",
                    ]);
                }
            }

            ScheduleSeat::whereIn('id', $oldSeatIds)->update(['is_available' => true]);

            foreach ($booking->passengers as $passenger) {
                $newSeat = $newSeats->get($seatLabels[$passenger->id]);
                $newSeat->update(['is_available' => false]);
                $passenger->update([
                    'schedule_seat_id' => $newSeat->id,
                    'qr_status'        => Passenger::QR_ACTIVE,
                    'scanned_at'       => null,
                ]);
            }

            $booking->update(['schedule_id' => $newSchedule->id]);
            $booking->generateAllQrCodes();

            return $booking->fresh([
                'schedule.busTrip.routeSegments.stop',
                'passengers.seat',
                'boardingStop',
                'dropStop',
            ]);
        });
    }
}
