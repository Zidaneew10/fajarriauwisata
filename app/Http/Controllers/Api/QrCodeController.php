<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\QrCodeService;
use Illuminate\Http\Request;

class QrCodeController extends Controller
{
    public function __construct(private QrCodeService $qrCodeService) {}

    public function scan(Request $request)
    {
        abort_unless($request->user()->hasAnyRole([
            'administrator',
            'customer_service',
            'petugas_loket',
            'driver',
            'manager',
        ]), 403, 'Anda tidak memiliki akses untuk scan QR code.');

        $validated = $request->validate([
            'qr_code_data' => 'required|string',
        ]);

        $result = $this->qrCodeService->scan($validated['qr_code_data']);

        return response()->json($result, $result['valid'] ? 200 : 422);
    }
}
