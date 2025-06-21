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
    \Log::info('Received amount from frontend:', ['amount' => $request->amount]);

    $request->validate(['amount' => 'required|numeric|min:1']);

    try {
        $amount = floatval($request->amount);
        $amountInPaise = intval(round($amount * 100)); // ₹49.99 → 4999

        \Log::info('Storing order with amount in paise:', ['amountInPaise' => $amountInPaise]);

        $order = $this->razorpay->order->create([
            'amount' => $amountInPaise,
            'currency' => 'INR',
            'receipt' => 'rcptid_' . time(),
            'payment_capture' => 1
        ]);

        // ✅ Save in DB
        Payment::create([
            'user_id' => Auth::check() ? Auth::id() : null,
            'razorpay_order_id' => $order->id,
            'amount' => $order->amount, // Already in paise
            'currency' => $order->currency,
            'status' => 'created'
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
            $payment = Payment::where('razorpay_order_id', $request->razorpay_order_id)->firstOrFail();

            // Razorpay signature verification
            $this->razorpay->utility->verifyPaymentSignature([
                'razorpay_order_id' => $request->razorpay_order_id,
                'razorpay_payment_id' => $request->razorpay_payment_id,
                'razorpay_signature' => $request->razorpay_signature,
            ]);

            // Update payment record
            $payment->update([
                'razorpay_payment_id' => $request->razorpay_payment_id,
                'razorpay_signature' => $request->razorpay_signature,
                'status' => 'success'
            ]);

            // Store payment ID in session for redirect
            session()->flash('payment_id', $payment->id);

            return redirect()->route('payment.success.page');

        } catch (\Exception $e) {
            Log::error('Payment verification failed: ' . $e->getMessage());
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


