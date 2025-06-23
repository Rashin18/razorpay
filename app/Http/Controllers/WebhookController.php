<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Payment;

class WebhookController extends Controller
{
    public function handleWebhook(Request $request)
    {
        $payload = $request->getContent();
        $signature = $request->header('X-Razorpay-Signature');
        $secret = env('RAZORPAY_WEBHOOK_SECRET');

        // 🔐 Signature verification
        $expected = hash_hmac('sha256', $payload, $secret);
        if (!hash_equals($expected, $signature)) {
            Log::error('❌ Webhook signature invalid.');
            return response('Invalid signature', 400);
        }

        $data = json_decode($payload, true);
        if (!isset($data['event'])) {
            Log::warning('⚠️ Webhook received with no event.');
            return response('No event specified', 400);
        }

        $event = $data['event'];
        Log::info("📩 Webhook Event Received: $event");

        match ($event) {
            'payment.captured'              => $this->handlePaymentCaptured($data['payload']['payment']['entity']),
            'payment.failed'                => $this->handlePaymentFailed($data['payload']['payment']['entity']),
            'order.paid'                    => Log::info('🧾 Order paid: ' . $data['payload']['order']['entity']['id']),
            'order.notification.delivered'  => Log::info('📤 Notification delivered.'),
            'order.notification.failed'     => Log::warning('📭 Notification delivery failed.'),
            'invoice.paid'                  => Log::info('✅ Invoice paid.'),
            'settlement.processed'          => Log::info('💸 Settlement processed.'),
            default                         => Log::info("⚙️ Unhandled webhook event: $event"),
        };

        return response('✅ Webhook processed', 200);
    }

    private function handlePaymentCaptured(array $payment)
    {
        Payment::updateOrCreate(
            ['razorpay_payment_id' => $payment['id']],
            [
                'razorpay_order_id' => $payment['order_id'],
                'status'            => 'success',
                'amount'            => $payment['amount'],
                'currency'          => $payment['currency'],
                'email'             => $payment['email'] ?? null,
            ]
        );

        Log::info("✅ Payment captured and saved: " . $payment['id']);
    }

    private function handlePaymentFailed(array $payment)
    {
        Payment::updateOrCreate(
            ['razorpay_payment_id' => $payment['id']],
            [
                'razorpay_order_id' => $payment['order_id'],
                'status'            => 'failed',
                'amount'            => $payment['amount'],
                'currency'          => $payment['currency'],
                'email'             => $payment['email'] ?? null,
            ]
        );

        Log::info("❌ Payment failed and recorded: " . $payment['id']);
    }
}



