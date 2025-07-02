<?php
/*
declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

// Initialize Laravel
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();

// Use Laravel's config instead of constants
$config = [
    'allowed_ips' => [
        '103.216.112.0/22',   // Razorpay production IP range
        '103.216.116.0/22',   // Razorpay production IP range
        '127.0.0.1',          // For local testing
        '::1'                 // For local IPv6 testing
    ],
    'rate_limit' => 2, // Minimum seconds between requests
];

try {
    // 1. Security Checks
    if (!app()->environment('local') && !validateIp($request, $config['allowed_ips'])) {
        throw new RuntimeException('IP address not allowed: '.$request->ip());
    }

    if (!app()->environment('local') && !rateLimit($request)) {
        throw new RuntimeException('Rate limit exceeded');
    }

    // 2. Get and validate input
    $payload = $request->getContent();
    if (empty($payload)) {
        throw new RuntimeException('Empty payload received');
    }

    $signature = $request->header('X-Razorpay-Signature');
    if (empty($signature)) {
        throw new RuntimeException('Missing signature header');
    }

    // 3. Verify signature
    $secret = config('razorpay.webhook_secret');
    $expectedSignature = hash_hmac('sha256', $payload, $secret);
    if (!hash_equals($expectedSignature, $signature)) {
        throw new RuntimeException('Invalid signature');
    }

    // 4. Parse payload
    $data = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
    if (!isset($data['event'], $data['payload'])) {
        throw new RuntimeException('Invalid payload structure');
    }

    // Log the incoming webhook
    \Log::channel('webhooks')->info("Received event: {$data['event']}", [
        'ip' => $request->ip(),
        'payload' => $data
    ]);

    // 5. Process through Laravel's controller
    $controller = new App\Http\Controllers\PaymentController();
    $response = $controller->handleWebhook($request);
    $response->send();

} catch (Throwable $e) {
    \Log::channel('webhooks')->error("Webhook processing failed", [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
        'payload' => $payload ?? null
    ]);

    http_response_code($e instanceof RuntimeException ? 400 : 500);
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'request_id' => uniqid()
    ]);
    exit;
}

// Helper functions
function validateIp($request, array $allowedIps): bool {
    $ip = $request->ip();
    foreach ($allowedIps as $range) {
        if (strpos($range, '/') !== false) {
            if (ipInRange($ip, $range)) return true;
        } elseif ($ip === $range) {
            return true;
        }
    }
    return false;
}

function ipInRange(string $ip, string $range): bool {
    list($subnet, $bits) = explode('/', $range);
    $ip_long = ip2long($ip);
    $subnet_long = ip2long($subnet);
    $mask = -1 << (32 - (int)$bits);
    return ($ip_long & $mask) === ($subnet_long & $mask);
}

function rateLimit($request): bool {
    $cacheKey = 'webhook_ratelimit_'.md5($request->ip());
    $lastCall = \Cache::get($cacheKey, 0);
    
    if (time() - $lastCall < config('webhook.rate_limit', 2)) {
        return false;
    }
    
    \Cache::put($cacheKey, time(), now()->addMinutes(5));
    return true;
}*/