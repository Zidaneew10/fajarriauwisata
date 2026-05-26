<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PaymentService;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function midtrans(Request $request, PaymentService $paymentService)
    {
        $paymentService->handleWebhook($request->json()->all());
        return response()->json(['message' => 'OK']);
    }
}
