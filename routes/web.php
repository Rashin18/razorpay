<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\WebhookController;

Route::get('/payment', [PaymentController::class, 'index']);
Route::post('/create-order', [PaymentController::class, 'createOrder']);
Route::post('/payment-success', [PaymentController::class, 'paymentSuccess']);
Route::post('/razorpay/webhook', [WebhookController::class, 'handleWebhook']);
