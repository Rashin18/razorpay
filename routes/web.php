<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\WebhookController;
/*Route::get('/', function () {
    return view('welcome');

});*/


Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/', [PaymentController::class, 'index'])->name('payment');
    Route::post('/create-order', [PaymentController::class, 'createOrder'])->name('create.order');
    Route::post('/payment-success', [PaymentController::class, 'paymentSuccess'])->name('payment.success');
    // routes/web.php

    Route::post('/webhook/razorpay', [WebhookController::class, 'handleWebhook']);
});
