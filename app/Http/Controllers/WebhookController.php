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

        // Validate webhook signature
        $expectedSignature = hash_hmac('sha256', $payload, $secret);

        if (!hash_equals($expectedSignature, $signature)) {
            Log::error('Webhook signature verification failed.');
            return response('Invalid signature', 400);
        }

        $data = json_decode($payload, true);

        Log::info('Webhook event:', $data);

        if (isset($data['event'])) {
            switch ($data['event']) {
                case 'payment.captured':
                    $this->handlePaymentCaptured($data['payload']['payment']['entity']);
                    break;

                case 'payment.failed':
                    $this->handlePaymentFailed($data['payload']['payment']['entity']);
                    break;

                // You can add more events here as needed
            }
        }

        return response('Webhook handled', 200);
    }

    private function handlePaymentCaptured($payment)
    {
        $paymentRecord = Payment::where('razorpay_payment_id', $payment['id'])->first();

        if ($paymentRecord) {
            $paymentRecord->update(['status' => 'success']);
            Log::info("Payment marked as successful: {$payment['id']}");
        } else {
            Log::warning("Payment not found: {$payment['id']}");
        }
    }

    private function handlePaymentFailed($payment)
    {
        $paymentRecord = Payment::where('razorpay_payment_id', $payment['id'])->first();

        if ($paymentRecord) {
            $paymentRecord->update(['status' => 'failed']);
            Log::info("Payment marked as failed: {$payment['id']}");
        } else {
            Log::warning("Failed payment not found: {$payment['id']}");
        }
    }
}
