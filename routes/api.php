<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ScheduleController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\PaymentController;
use Illuminate\Support\Facades\Route;

Route::post('/midtrans/webhook', [PaymentController::class, 'webhook']);

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    // Jadwal
    Route::get('/schedules',                                [ScheduleController::class, 'index']);
    Route::get('/schedules/{schedule}/buses',               [ScheduleController::class, 'buses']);
    Route::get('/schedule-buses/{scheduleBus}/seats',       [ScheduleController::class, 'seats']);

    // Promo
    Route::post('/promo/check', [BookingController::class, 'checkPromo']);

    // Booking
    Route::get('/bookings',                    [BookingController::class, 'index']);
    Route::post('/bookings',                   [BookingController::class, 'store']);
    Route::get('/bookings/{booking}',          [BookingController::class, 'show']);
    Route::post('/bookings/{booking}/cancel',  [BookingController::class, 'cancel']);
    Route::post('/bookings/{booking}/payment', [PaymentController::class, 'getSnapToken']);
});
