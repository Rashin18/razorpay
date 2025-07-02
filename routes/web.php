<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentController;
//use App\Http\Controllers\WebhookController;
use App\Http\Middleware\VerifyCsrfToken;
use App\Models\WebhookEvent;

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

// 🔔 Razorpay Webhook endpoint (POST from Razorpay)
// Redirect to the direct PHP endpoint
/*Route::any('/webhook/razorpay', function() {
    return redirect('/razorpay-webhook.php', 307);
})->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);*/

Route::any('/webhook/razorpay', [PaymentController::class, 'handleWebhook'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

// Test database connection
Route::get('/test-db', function() {
    try {
        DB::connection()->getPdo();
        return 'Connected successfully to: ' . DB::connection()->getDatabaseName();
    } catch (\Exception $e) {
        return 'Connection failed: ' . $e->getMessage();
    }
});

// Test model saving
Route::get('/test-model', function() {
    try {
        $event = WebhookEvent::create([
            'event_type' => 'test.event',
            'payload' => ['test' => true],
            'status' => 'received'
        ]);
        return response()->json($event);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
});

// Test webhook processing
Route::get('/test-webhook', function() {
    $testData = [
        'event' => 'payment.captured',
        'payload' => [
            'payment' => [
                'entity' => [
                    'id' => 'pay_'.Str::random(14),
                    'amount' => 1000,
                    'currency' => 'INR'
                ]
            ]
        ]
    ];
    
    $request = new \Illuminate\Http\Request([], [], [], [], [], [], json_encode($testData));
    $request->headers->set('X-Razorpay-Signature', 'test-signature');
    
    $controller = new PaymentController();
    return $controller->handleWebhook($request);
});
Route::get('/test-secret', function() {
    return 'Webhook secret: ' . config('razorpay.webhook_secret');
});