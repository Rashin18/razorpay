<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentController;
//use App\Http\Controllers\WebhookController;


// 🏠 Home - Show payment form
Route::get('/', [PaymentController::class, 'index']);

// 💳 Create Razorpay order (AJAX)
Route::post('/create-order', [PaymentController::class, 'createOrder']);

// ✅ Razorpay payment handler (called from JS handler after success)
Route::post('/payment-success', [PaymentController::class, 'paymentSuccess'])->name('payment.success');

// ✅ Show payment success page (GET, after session redirect)
Route::get('/payment-success', [PaymentController::class, 'showSuccessPage'])->name('payment.success.page');

// ❌ Show payment failure page (optional)
Route::get('/payment-failure', [PaymentController::class, 'paymentFailure'])->name('payment.failure');

// 📄 View all past payments (must be logged in)
Route::get('/my-payments', [PaymentController::class, 'userPayments']);


Route::any('/webhook/razorpay', [PaymentController::class, 'handleWebhook'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

