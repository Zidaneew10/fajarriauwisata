<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\ScheduleController;
use App\Http\Controllers\Api\WebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/midtrans/webhook', [WebhookController::class, 'midtrans']);

Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:10,1');
Route::post('/login',    [AuthController::class, 'login'])->middleware('throttle:10,1');

Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/schedules',                    [ScheduleController::class, 'index']);
    Route::get('/schedules/{schedule}/seats',   [ScheduleController::class, 'seats']);

    Route::get('/bookings',                     [BookingController::class, 'index']);
    Route::post('/bookings',                    [BookingController::class, 'store']);
    Route::get('/bookings/{booking}',           [BookingController::class, 'show']);
    Route::post('/bookings/{booking}/payment',  [BookingController::class, 'payment']);
    Route::post('/bookings/{booking}/cancel',   [BookingController::class, 'cancel']);
    Route::post('/promo/check',                 [BookingController::class, 'checkPromo']);
});
