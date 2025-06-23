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

        $expected = hash_hmac('sha256', $payload, $secret);

        if (!hash_equals($expected, $signature)) {
            Log::error('❌ Webhook signature invalid.');
            return response('Invalid signature', 400);
        }

        $data = json_decode($payload, true);

        if (!isset($data['event'])) {
            Log::warning('⚠️ Webhook without event received.');
            return response('No event specified', 400);
        }

        $event = $data['event'];

        switch ($event) {
            case 'payment.captured':
                $this->handlePaymentCaptured($data['payload']['payment']['entity']);
                break;
            case 'payment.failed':
                $this->handlePaymentFailed($data['payload']['payment']['entity']);
                break;
            default:
                Log::info("ℹ️ Unhandled event: " . $event);
        }

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

        Log::info("✅ Payment captured: " . $payment['id']);
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

        Log::info("❌ Payment failed: " . $payment['id']);
    }
}


