/<?php
// Enable full error reporting
/*error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start timing for performance logging
$startTime = microtime(true);

// Initialize Laravel application
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Log initialization
Log::channel('webhooks')->info('Webhook endpoint initialized', [
    'memory_usage' => memory_get_usage(),
    'load_time' => microtime(true) - $startTime
]);

try {
    // Capture the request
    $request = Illuminate\Http\Request::capture();
    
    // Log incoming request
    Log::channel('webhooks')->debug('Incoming webhook request', [
        'headers' => $request->headers->all(),
        'ip' => $request->ip(),
        'method' => $request->method()
    ]);

    // Verify webhook secret is configured
    $secret = config('razorpay.webhook_secret');
    if (empty($secret)) {
        throw new RuntimeException('RAZORPAY_WEBHOOK_SECRET is not configured');
    }

    // Get raw payload and signature
    $payload = $request->getContent();
    $signature = $request->header('X-Razorpay-Signature');
    
    // Verify signature
    $expectedSignature = hash_hmac('sha256', $payload, $secret);
    if (!hash_equals($expectedSignature, $signature)) {
        throw new RuntimeException('Invalid webhook signature');
    }

    // Parse JSON payload
    $data = json_decode($payload, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new RuntimeException('Invalid JSON payload: '.json_last_error_msg());
    }

    // Handle the webhook through Laravel's controller
    $controller = new App\Http\Controllers\PaymentController();
    $response = $controller->handleWebhook($request);
    
    // Send response
    $response->send();

} catch (Throwable $e) {
    // Detailed error logging
    Log::channel('webhooks')->error('Webhook processing failed', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
        'payload' => $payload ?? null,
        'processing_time' => microtime(true) - $startTime
    ]);

    // Return error response
    http_response_code($e instanceof RuntimeException ? 400 : 500);
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'request_id' => uniqid()
    ]);
    exit;
}

// Log successful processing
Log::channel('webhooks')->info('Webhook processed successfully', [
    'processing_time' => microtime(true) - $startTime,
    'memory_peak' => memory_get_peak_usage()
]);*/