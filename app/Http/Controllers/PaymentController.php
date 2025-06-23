<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Razorpay\Api\Api;
use App\Models\Payment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    private $razorpay;

    public function __construct()
    {
        $this->razorpay = new Api(
            'rzp_test_uLGlQp5vZDcWTf', // your test key
            'E8L6FwLh973JjjRpvTWPSUnz' // your test secret
        );
    }

    public function index()
    {
        return view('payment');
    }

    // ✅ Unchanged — your original createOrder method
    public function createOrder(Request $request)
    {
        $request->validate(['amount' => 'required|numeric|min:1']);

        try {
            $amountInPaise = intval($request->amount * 100);
            \Log::info('Amount input:', ['rupees' => $request->amount, 'paise' => $amountInPaise]);

            $order = $this->razorpay->order->create([
                'amount' => $amountInPaise,
                'currency' => 'INR',
                'receipt' => 'rcptid_' . time(),
                'payment_capture' => 1
            ]);

            return response()->json([
                'id' => $order->id,
                'amount' => $order->amount,
                'currency' => $order->currency
            ]);
        } catch (\Exception $e) {
            \Log::error('Razorpay order creation failed: ' . $e->getMessage());
            return response()->json(['error' => 'Payment initiation failed'], 500);
        }
    }

    // ✅ POST handler: Verify payment and store result
  public function paymentSuccess(Request $request)
{
    $request->validate([
        'razorpay_order_id' => 'required',
        'razorpay_payment_id' => 'required',
        'razorpay_signature' => 'required'
    ]);

    try {
        // ✅ Verify signature
        $this->razorpay->utility->verifyPaymentSignature([
            'razorpay_order_id'   => $request->razorpay_order_id,
            'razorpay_payment_id' => $request->razorpay_payment_id,
            'razorpay_signature'  => $request->razorpay_signature,
        ]);

        // ✅ If signature is valid, create or update payment in DB
        $payment = Payment::updateOrCreate(
            ['razorpay_order_id' => $request->razorpay_order_id],
            [
                'user_id'              => optional(Auth::user())->id,
                'razorpay_payment_id'  => $request->razorpay_payment_id,
                'razorpay_signature'   => $request->razorpay_signature,
                'amount'               => $request->amount ?? 0, // optional, or save separately
                'currency'             => 'INR',
                'status'               => 'success'
            ]
        );

        return view('payment-success', compact('payment'));

    } catch (\Exception $e) {
        \Log::error('Payment verification failed: ' . $e->getMessage());
        return view('payment-failure');
    }
}




    // ✅ GET route to display success page
    public function showSuccessPage()
    {
        $paymentId = session('payment_id');

        if (!$paymentId) {
            return view('payment-failure')->with('error', 'Payment not found.');
        }

        $payment = Payment::find($paymentId);

        if (!$payment) {
            return view('payment-failure')->with('error', 'Payment record missing.');
        }

        return view('payment-success', compact('payment'));
    }

    public function paymentFailure()
    {
        return view('payment-failure');
    }

    public function userPayments()
    {
        $payments = Payment::where('user_id', Auth::id())->latest()->get();
        return view('my-payments', compact('payments'));
    }
}

