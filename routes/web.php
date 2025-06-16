<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\WebhookController;
/*Route::get('/', function () {
    return view('welcome');

});*/


Route::get('/', [PaymentController::class, 'index']);
Route::post('/create-order', [PaymentController::class, 'createOrder']);
//Route::post('/payment-success', [PaymentController::class, 'webhook'])->name('payment.webhook');
Route::get('/my-payments', [PaymentController::class, 'userPayments'])->middleware('auth');
// routes/web.php
// For Razorpay webhook (POST)
Route::post('/webhook', [PaymentController::class, 'webhook']);

// For user redirection after payment (GET)
Route::post('/payment-success', [PaymentController::class, 'paymentSuccess']);


Route::post('/webhook/razorpay', [WebhookController::class, 'handleWebhook']);
