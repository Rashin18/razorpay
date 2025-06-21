<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\WebhookController;

// Payment UI Page
Route::get('/', [PaymentController::class, 'index']);

// Order creation (AJAX)
Route::post('/create-order', [PaymentController::class, 'createOrder']);

// Payment success callback (from Razorpay JS handler)
Route::post('/payment-success', [PaymentController::class, 'paymentSuccess'])->name('payment.success');
Route::get('/payment-success-page', function () {
    return view('payment-success');
});

// Logged-in user's payment history
Route::get('/my-payments', [PaymentController::class, 'userPayments'])->middleware('auth');

// Razorpay Webhook endpoint (must match what's set in dashboard)
Route::post('/webhook/razorpay', [WebhookController::class, 'handleWebhook'])->name('webhook.razorpay');

