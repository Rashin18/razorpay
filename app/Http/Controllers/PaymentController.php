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
            'rzp_test_uLGlQp5vZDcWTf',
            'E8L6FwLh973JjjRpvTWPSUnz'
        );
    }

    public function index()
    {
        return view('payment');
    }

    // ✅ Leave this as-is (no changes)
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

    // ✅ Handles Razorpay success callback
    public function paymentSuccess(Request $request)
    {
        $request->validate([
            'razorpay_order_id' => 'required',
            'razorpay_payment_id' => 'required',
            'razorpay_signature' => 'required'
        ]);

        try {
            $payment = Payment::where('razorpay_order_id', $request->razorpay_order_id)->firstOrFail();

            $this->razorpay->utility->verifyPaymentSignature([
                'razorpay_order_id' => $request->razorpay_order_id,
                'razorpay_payment_id' => $request->razorpay_payment_id,
                'razorpay_signature' => $request->razorpay_signature,
            ]);

            $payment->update([
                'razorpay_payment_id' => $request->razorpay_payment_id,
                'razorpay_signature' => $request->razorpay_signature,
                'status' => 'success'
            ]);

            // ✅ Flash payment ID to session
            session()->flash('payment_id', $payment->id);

            // ✅ Redirect to GET route
            return redirect()->route('payment.success.page');

        } catch (\Exception $e) {
            \Log::error('Payment verification failed: ' . $e->getMessage());
            return view('payment-failure');
        }
    }

    // ✅ New: Show payment success page
    public function showSuccessPage()
    {
        $paymentId = session('payment_id');

        if (!$paymentId) {
            abort(404, 'Payment not found');
        }

        $payment = Payment::findOrFail($paymentId);
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

