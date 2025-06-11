<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentController;

/*Route::get('/', function () {
    return view('welcome');

});*/
Route::get('/', [PaymentController::class, 'index'])->name('payment.index');
Route::post('/create-order', [PaymentController::class, 'createOrder'])->name('payment.create-order');
Route::post('/payment-success', [PaymentController::class, 'paymentSuccess'])->name('payment.success');
Route::post('/payment-failure', [PaymentController::class, 'paymentFailure'])->name('payment.failure');
Route::post('/razorpay-webhook', [PaymentController::class, 'handleWebhook'])->name('payment.webhook');
