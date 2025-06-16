<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\WebhookController;
/*Route::get('/', function () {
    return view('welcome');

});*/


Route::get('/', [PaymentController::class, 'index']);
Route::post('/create-order', [PaymentController::class, 'createOrder']);
Route::post('/payment-success', [PaymentController::class, 'handlePaymentSuccess']);

Route::get('/my-payments', [PaymentController::class, 'userPayments'])->middleware('auth');
// routes/web.php

Route::post('/webhook/razorpay', [WebhookController::class, 'handleWebhook']);
