<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Passenger;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QrCodeService
{
    private const QR_SVG_SIZE = 600;

    public function generateTicketQrSvg(string $qrData): string
    {
        try {
            $svg = (string) QrCode::format('svg')
                ->size(self::QR_SVG_SIZE)
                ->margin(1)
                ->errorCorrection('M')
                ->generate($qrData);

            return $this->normalizeQrSvg($svg);
        } catch (\Throwable $e) {
            Log::warning('QR generation failed', [
                'error' => $e->getMessage()
            ]);
        }

        return $this->fallbackQrSvg();
    }

    private function normalizeQrSvg(string $svg): string
    {
        $size = self::QR_SVG_SIZE;

        $svg = preg_replace('/<\?xml.*?\?>/', '', $svg) ?? $svg;
        $svg = trim($svg);

        if (!str_contains($svg, 'viewBox')) {
            $svg = preg_replace(
                '/<svg/',
                '<svg viewBox="0 0 ' . $size . ' ' . $size . '"',
                $svg,
                1
            ) ?? $svg;
        }

        $svg = preg_replace('/\swidth="[^"]*"/', '', $svg) ?? $svg;
        $svg = preg_replace('/\sheight="[^"]*"/', '', $svg) ?? $svg;

        return preg_replace(
            '/<svg/',
            '<svg width="' . $size . '" height="' . $size . '"',
            $svg,
            1
        ) ?? $svg;
    }

    private function fallbackQrSvg(): string
    {
        $size = self::QR_SVG_SIZE;

        return "<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 {$size} {$size}' width='{$size}' height='{$size}'>
            <rect width='{$size}' height='{$size}' fill='white'/>
            <text x='50%' y='50%' text-anchor='middle' fill='#666' font-size='20'>
                QR tidak tersedia
            </text>
        </svg>";
    }

    public function scan(string $qrCodeData): array
    {
        $payload = $this->decryptPayload($qrCodeData);

        return DB::transaction(function () use ($qrCodeData, $payload) {

            $passenger = Passenger::with([
                'booking.schedule.busTrip.routeSegments.stop',
                'booking.boardingStop',
                'booking.dropStop',
                'seat',
            ])
                ->lockForUpdate()
                ->find($payload['passenger_id']);

            if (!$passenger || $passenger->qr_code_data !== $qrCodeData) {
                throw ValidationException::withMessages([
                    'qr_code_data' => 'QR tidak valid.',
                ]);
            }

            $booking = $passenger->booking;

            // 🔥 FIX: expired check
            if ($booking->expired_at && now()->greaterThan($booking->expired_at)) {
                return $this->invalidResponse(
                    'expired',
                    'Booking sudah kadaluarsa.',
                    $passenger,
                    $booking
                );
            }

            if ($passenger->qr_status === Passenger::QR_USED) {
                return $this->invalidResponse(
                    'already_used',
                    'QR sudah pernah digunakan.',
                    $passenger,
                    $booking
                );
            }

            if ($passenger->qr_status === Passenger::QR_CANCELLED) {
                return $this->invalidResponse(
                    'cancelled',
                    'QR sudah dibatalkan.',
                    $passenger,
                    $booking
                );
            }

            if (!in_array($booking->status, [
                Booking::STATUS_PAID,
                Booking::STATUS_CONFIRMED
            ], true)) {
                return $this->invalidResponse(
                    'not_paid',
                    'Booking belum dibayar.',
                    $passenger,
                    $booking
                );
            }

            $passenger->update([
                'qr_status'  => Passenger::QR_USED,
                'scanned_at' => now(),
            ]);

            if ($booking->status === Booking::STATUS_PAID) {
                $booking->update([
                    'status' => Booking::STATUS_CONFIRMED
                ]);
            }

            return [
                'valid'   => true,
                'message' => 'Boarding berhasil.',
                'data'    => $this->formatPassengerData($passenger, $booking),
            ];
        });
    }

    private function decryptPayload(string $qrCodeData): array
    {
        try {
            $decoded = Crypt::decryptString($qrCodeData);
            $payload = json_decode($decoded, true);

        } catch (\Throwable $e) {
            Log::warning('QR decrypt failed', [
                'error' => $e->getMessage()
            ]);

            throw ValidationException::withMessages([
                'qr_code_data' => 'QR rusak atau tidak valid.',
            ]);
        }

        if (!is_array($payload) || !isset($payload['passenger_id'])) {
            throw ValidationException::withMessages([
                'qr_code_data' => 'QR tidak lengkap.',
            ]);
        }

        return $payload;
    }

    private function invalidResponse(string $status, string $message, Passenger $passenger, Booking $booking): array
    {
        return [
            'valid'   => false,
            'status'  => $status,
            'message' => $message,
            'data'    => $this->formatPassengerData($passenger, $booking),
        ];
    }

    private function formatPassengerData(Passenger $passenger, ?Booking $booking = null): array
    {
        $booking ??= $passenger->booking;
        $schedule = $booking->schedule;
        $busTrip  = $schedule?->busTrip;

        $segments = $busTrip?->routeSegments
            ?->load('stop')
            ->sortBy('sequence');

        $origin = $segments?->first()?->stop?->city ?? '-';
        $destination = $segments?->last()?->stop?->city ?? '-';

        $departureDate = optional($schedule?->departure_date)->format('d M Y');

        return [
            'passenger_id'   => $passenger->id,
            'passenger_name' => $passenger->name,
            'seat'           => $passenger->seat?->label,
            'qr_status'      => $passenger->qr_status,
            'scanned_at'     => optional($passenger->scanned_at)?->format('d M Y H:i:s'),

            'booking_code'   => $booking->booking_code,
            'booking_status' => $booking->status,

            'departure'      => trim($departureDate . ' ' . substr((string)$schedule?->departure_time, 0, 5)),

            'boarding'       => $booking->boardingStop?->name ?? '-',
            'drop'           => $booking->dropStop?->name ?? '-',

            'trip_number'    => $busTrip?->trip_number ?? '-',
            'class_type'     => $busTrip?->class_type ?? '-',

            'route'          => "{$origin} → {$destination}",
        ];
    }
}
