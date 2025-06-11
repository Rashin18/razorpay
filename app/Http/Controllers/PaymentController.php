<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Razorpay\Api\Api;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    private $razorpay;

    public function __construct()
    {
        $this->razorpay = new Api(config('razorpay.key'), config('razorpay.secret'));
    }

    public function index()
    {
        return view('payment');
    }

    public function createOrder(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
        ]);

        $amount = $request->amount * 100; // Razorpay uses paise/pennies

        $order = $this->razorpay->order->create([
            'amount' => $amount,
            'currency' => 'INR',
            'receipt' => 'order_' . time(),
            'payment_capture' => 1
        ]);

        // Save order details to database
        Payment::create([
            'razorpay_order_id' => $order->id,
            'amount' => $request->amount,
            'currency' => 'INR',
            'status' => 'created'
        ]);

        return response()->json($order);
    }

    public function paymentSuccess(Request $request)
    {
        $request->validate([
            'razorpay_payment_id' => 'required',
            'razorpay_order_id' => 'required',
            'razorpay_signature' => 'required',
        ]);

        $payment = Payment::where('razorpay_order_id', $request->razorpay_order_id)->firstOrFail();

        // Verify signature
        $attributes = [
            'razorpay_order_id' => $request->razorpay_order_id,
            'razorpay_payment_id' => $request->razorpay_payment_id,
            'razorpay_signature' => $request->razorpay_signature,
        ];

        try {
            $this->razorpay->utility->verifyPaymentSignature($attributes);
            
            $payment->update([
                'razorpay_payment_id' => $request->razorpay_payment_id,
                'razorpay_signature' => $request->razorpay_signature,
                'status' => 'success'
            ]);

            return view('payment-success', ['payment' => $payment]);

        } catch (\Exception $e) {
            Log::error('Payment verification failed: ' . $e->getMessage());
            $payment->update(['status' => 'failed']);
            return redirect()->route('payment.failure')->with('error', 'Payment verification failed');
        }
    }

    public function paymentFailure()
    {
        return view('payment-failure');
    }

    public function handleWebhook(Request $request)
    {
        $webhookSecret = config('razorpay.webhook_secret');
        $webhookSignature = $request->header('X-Razorpay-Signature');

        $payload = $request->getContent();

        try {
            $this->razorpay->utility->verifyWebhookSignature($payload, $webhookSignature, $webhookSecret);

            $data = json_decode($payload, true);
            $event = $data['event'];

            Log::info('Razorpay Webhook: ' . $event, $data);

            if ($event === 'payment.captured') {
                $this->handlePaymentCaptured($data['payload']['payment']['entity']);
            }
            // Handle other events as needed

            return response()->json(['status' => 'success']);

        } catch (\Exception $e) {
            Log::error('Webhook error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    protected function handlePaymentCaptured($paymentData)
    {
        $payment = Payment::where('razorpay_payment_id', $paymentData['id'])->first();

        if (!$payment) {
            // Payment not found in database, create new record
            $payment = Payment::create([
                'razorpay_payment_id' => $paymentData['id'],
                'razorpay_order_id' => $paymentData['order_id'],
                'amount' => $paymentData['amount'] / 100,
                'currency' => $paymentData['currency'],
                'status' => 'captured',
                'payload' => $paymentData
            ]);
        } else {
            // Update existing payment
            $payment->update([
                'status' => 'captured',
                'payload' => $paymentData
            ]);
        }

        // Here you can trigger other actions like sending emails, updating user subscriptions, etc.
    }
}