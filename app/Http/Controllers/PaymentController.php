<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Razorpay\Api\Api;
use App\Models\Payment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\WebhookEvent;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    private $razorpay;

    public function __construct()
    {
        $this->razorpay = new Api(
            config('razorpay.key'),
            config('razorpay.secret')
        );
    }

    public function index()
    {
        return view('payment');
    }

    public function createOrder(Request $request)
    {
        $request->validate(['amount' => 'required|numeric|min:1']);

        try {
            $amountInPaise = intval($request->amount * 100);
            Log::info('Amount input:', ['rupees' => $request->amount, 'paise' => $amountInPaise]);

            $order = $this->razorpay->order->create([
                'amount' => $amountInPaise,
                'currency' => 'INR',
                'receipt' => 'rcptid_' . time(),
                'payment_capture' => 1
            ]);

            Payment::create([
                'user_id' => Auth::check() ? Auth::id() : null,
                'razorpay_order_id' => $order['id'],
                'amount' => $order['amount'],
                'currency' => $order['currency'],
                'status' => 'created',
            ]);

            return response()->json([
                'id' => $order->id,
                'amount' => $order->amount,
                'currency' => $order->currency
            ]);
        } catch (\Exception $e) {
            Log::error('Razorpay order creation failed: ' . $e->getMessage());
            return response()->json(['error' => 'Payment initiation failed'], 500);
        }
    }

    public function paymentSuccess(Request $request)
    {
        $request->validate([
            'razorpay_order_id' => 'required',
            'razorpay_payment_id' => 'required',
            'razorpay_signature' => 'required'
        ]);

        try {
            $this->razorpay->utility->verifyPaymentSignature([
                'razorpay_order_id' => $request->razorpay_order_id,
                'razorpay_payment_id' => $request->razorpay_payment_id,
                'razorpay_signature' => $request->razorpay_signature,
            ]);

            $payment = Payment::updateOrCreate(
                ['razorpay_order_id' => $request->razorpay_order_id],
                [
                    'razorpay_payment_id' => $request->razorpay_payment_id,
                    'razorpay_signature' => $request->razorpay_signature,
                    'status' => 'success'
                ]
            );

            return view('payment-success', compact('payment'));

        } catch (\Exception $e) {
            Log::error('Payment verification failed: ' . $e->getMessage());
            return view('payment-failure');
        }
    }

    public function showSuccessPage()
    {
        $paymentId = session('payment_id');

        if (!$paymentId) {
            return view('payment-failure')->with('error', 'Payment not found.');
        }

        $payment = Payment::find($paymentId);
        return $payment 
            ? view('payment-success', compact('payment'))
            : view('payment-failure')->with('error', 'Payment record missing.');
    }

    public function paymentFailure()
    {
        return view('payment-failure');
    }

    public function userPayments()
{
    $payments = Payment::where('user_id', Auth::id())->latest()->get();
    $webhookEvents = WebhookEvent::latest()->take(50)->get(); // Adjust as needed
    
    return view('my-payments', compact('payments', 'webhookEvents'));
}

    /**
     * Handle Razorpay webhook events
     */
    public function handleWebhook(Request $request)
    {
        if (!$request->isMethod('post')) {
            return response()->json(['error' => 'Method Not Allowed'], 405);
        }
        \Log::channel('webhooks')->debug('Request received', [
            'method' => $request->method(),
            'headers' => $request->headers->all(),
            'content' => $request->getContent()
        ]);
    
        DB::beginTransaction();
        
        try {
            // Enhanced request logging
            Log::channel('webhooks')->info('Webhook received', [
                'ip' => $request->ip(),
                'headers' => array_filter($request->headers->all(), function($key) {
                    return in_array(strtolower($key), ['content-type', 'x-razorpay-signature']);
                }, ARRAY_FILTER_USE_KEY),
                'payload_size' => strlen($request->getContent())
            ]);
    
            // 4. Validate and process payload
            $payload = $request->getContent();
            if (empty($payload)) {
                throw new \RuntimeException('Empty payload received');
            }
    
            $data = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
            
            if (!isset($data['event'], $data['payload'])) {
                throw new \RuntimeException('Invalid payload structure');
            }
    
            // 5. Verify signature
            $secret = config('razorpay.webhook_secret');
            if (empty($secret)) {
                throw new \RuntimeException('Webhook secret not configured');
            }
    
            $signature = $request->header('X-Razorpay-Signature');
            if (empty($signature)) {
                throw new \RuntimeException('Missing X-Razorpay-Signature header');
            }
    
            if (!$this->verifySignature($payload, $signature, $secret)) {
                Log::channel('webhooks')->error('Signature verification failed', [
                    'expected' => hash_hmac('sha256', $payload, $secret),
                    'received' => $signature,
                    'payload_sample' => Str::limit($payload, 200)
                ]);
                throw new \RuntimeException('Signature verification failed');
            }
    
            // 6. Store and process event
            $webhookEvent = WebhookEvent::create([
                'event_type' => $data['event'],
                'entity_id' => $this->extractEntityId($data),
                'payload' => $data,
                'status' => 'received'
            ]);
    
            if (!$webhookEvent->exists) {
                throw new \RuntimeException('Failed to persist webhook event');
            }
    
            $this->processWebhookEvent($data, $webhookEvent);
            $webhookEvent->update(['status' => 'processed']);
    
            DB::commit();
            
            Log::channel('webhooks')->info('Webhook processed successfully', [
                'event_id' => $webhookEvent->id,
                'processing_time_ms' => microtime(true) - LARAVEL_START
            ]);
    
            return response()->json([
                'status' => 'success',
                'event_id' => $webhookEvent->id,
                'processed_at' => now()->toDateTimeString()
            ]);
    
        } catch (\JsonException $e) {
            $error = 'Invalid JSON: ' . $e->getMessage();
            $statusCode = 400;
        } catch (\RuntimeException $e) {
            $error = $e->getMessage();
            $statusCode = 400;
        } catch (\Exception $e) {
            $error = 'Server error: ' . $e->getMessage();
            $statusCode = 500;
        }
    
        DB::rollBack();
        
        // 7. Error handling
        Log::channel('webhooks')->error('Webhook processing failed', [
            'error' => $error,
            'payload' => $payload ?? null,
            'trace' => $e->getTraceAsString() ?? null
        ]);
    
        try {
            $failedEvent = WebhookEvent::create([
                'event_type' => $data['event'] ?? 'unknown',
                'entity_id' => $this->extractEntityId($data) ?? null,
                'payload' => $data ?? null,
                'status' => 'failed',
                'processing_errors' => $error
            ]);
            
            Log::channel('webhooks')->error('Failure recorded', [
                'event_id' => $failedEvent->id,
                'error_type' => get_class($e)
            ]);
        } catch (\Exception $dbError) {
            Log::channel('webhooks')->critical('Failed to log failure', [
                'original_error' => $error,
                'db_error' => $dbError->getMessage()
            ]);
        }
    
        return response()->json([
            'status' => 'error',
            'message' => $error,
            'request_id' => Str::uuid(),
            'documentation' => 'https://razorpay.com/docs/webhooks/'
        ], $statusCode ?? 500);
    }
    private function processWebhookEvent(array $data, WebhookEvent $webhookEvent)
    {
        switch ($data['event']) {
            case 'payment.captured':
                $this->handlePaymentCaptured($data['payload']['payment']['entity']);
                break;
                
            case 'payment.failed':
                $this->handlePaymentFailed($data['payload']['payment']['entity']);
                break;
                
            default:
                Log::info("Unhandled webhook event type: {$data['event']}");
        }

        $webhookEvent->update(['status' => 'processed']);
    }

    private function verifySignature(string $payload, string $signature, string $secret): bool
    {
        $expected = hash_hmac('sha256', $payload, $secret);
        return hash_equals($expected, $signature);
    }

    private function storeWebhookEvent(array $data): WebhookEvent
{
    try {
        Log::debug('Attempting to store webhook event', $data);
        
        $event = WebhookEvent::create([
            'event_type' => $data['event'],
            'entity_id' => $this->extractEntityId($data),
            'payload' => $data,
            'status' => 'received'
        ]);

        Log::debug('WebhookEvent created:', $event->toArray());
        Log::info('Webhook stored successfully', ['id' => $event->id]);

        return $event;
    } catch (\Exception $e) {
        Log::error('Webhook storage failed', [
            'error' => $e->getMessage(),
            'data' => $data
        ]);
        throw $e;
    }
}

    private function extractEntityId(array $data): ?string
    {
        $entityTypes = [
            'payment' => ['payment.captured', 'payment.failed'],
            'order' => ['order.paid'],
            'invoice' => ['invoice.paid'],
            'settlement' => ['settlement.processed']
        ];

        foreach ($entityTypes as $type => $events) {
            if (in_array($data['event'], $events)) {
                return $data['payload'][$type]['entity']['id'] ?? null;
            }
        }

        return null;
    }

    private function handlePaymentCaptured(array $payment): void
{
    $paymentRecord = Payment::updateOrCreate(
        ['razorpay_payment_id' => $payment['id']],
        [
            'razorpay_order_id' => $payment['order_id'],
            'status' => 'captured',
            'amount' => $payment['amount'],
            'currency' => $payment['currency'],
            'method' => $payment['method'] ?? null,
            'email' => $payment['email'] ?? null,
            'contact' => $payment['contact'] ?? null,
            'notes' => $payment['notes'] ?? null,
        ]
    );

    // Now actually using the paymentRecord
    Log::info("Payment captured", [
        'payment_id' => $payment['id'],
        'record_id' => $paymentRecord->id,
        'amount' => $paymentRecord->amount,
        'status' => $paymentRecord->status
    ]);
}

private function handlePaymentFailed(array $payment): void
{
    $paymentRecord = Payment::updateOrCreate(
        ['razorpay_payment_id' => $payment['id']],
        [
            'razorpay_order_id' => $payment['order_id'],
            'status' => 'failed',
            'amount' => $payment['amount'],
            'currency' => $payment['currency'],
            'error_code' => $payment['error_code'] ?? null,
            'error_description' => $payment['error_description'] ?? null,
        ]
    );

    // Now actually using the paymentRecord
    Log::warning("Payment failed", [
        'payment_id' => $payment['id'],
        'record_id' => $paymentRecord->id,
        'error' => $paymentRecord->error_description ?? 'Unknown error',
        'status' => $paymentRecord->status
    ]);
}

    protected function verifyWebhook(Request $request): void
{
    $secret = config('razorpay.webhook_secret');
    if (empty($secret)) {
        throw new \RuntimeException('Webhook secret not configured');
    }

    $payload = $request->getContent();
    $signature = $request->header('X-Razorpay-Signature');
    
    \Log::debug('Webhook Verification', [
        'received' => $signature,
        'expected' => hash_hmac('sha256', $payload, $secret),
        'payload' => $payload
    ]);
    
    if (!hash_equals(hash_hmac('sha256', $payload, $secret), $signature)) {
        throw new \RuntimeException('Signature verification failed');
    }
}
}