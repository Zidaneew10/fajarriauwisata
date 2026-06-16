<?php

namespace App\Filament\Pages;

use App\Filament\Traits\HasRoleAccess;
use App\Services\QrCodeService;
use Filament\Pages\Page;
use Illuminate\Validation\ValidationException;

class ScanQrCode extends Page
{
    use HasRoleAccess;

    protected static ?string $navigationIcon = 'heroicon-o-qr-code';

    protected static ?string $navigationGroup = 'Tiket';

    protected static ?string $navigationLabel = 'Scan QR Code';

    protected static ?string $title = 'Scan QR Code';

    protected static ?string $slug = 'scan-qr-code';

    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.pages.scan-qr-code';

    // Livewire properties
    public ?string $qrCodeData = null;
    public ?array $scanResult = null;
    public bool $isScanning = true;

    public static function canAccess(): bool
    {
        return static::canManageTickets() || static::isDriver();
    }

    public function scanQrCode(string $qrData): void
    {
        $this->qrCodeData = $qrData;
        $this->scanResult = null;

        try {
            $service = app(QrCodeService::class);
            $result = $service->scan($qrData);

            $this->scanResult = $result;
            $this->isScanning = false;
        } catch (ValidationException $e) {
            $this->scanResult = [
                'valid'   => false,
                'message' => collect($e->errors())->flatten()->first() ?? 'QR code tidak valid.',
                'data'    => null,
            ];
            $this->isScanning = false;
        } catch (\Throwable $e) {
            $this->scanResult = [
                'valid'   => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
                'data'    => null,
            ];
            $this->isScanning = false;
        }
    }

    public function resetScanner(): void
    {
        $this->qrCodeData = null;
        $this->scanResult = null;
        $this->isScanning = true;
    }
}
