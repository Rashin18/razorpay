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

    public function createOrder(Request $request)
    {
        $request->validate(['amount' => 'required|numeric|min:1|max:100000']);
        try {
            $api = new Api('rzp_test_uLGlQp5vZDcWTf', 'E8L6FwLh973JjjRpvTWPSUnz');
            $amount = $request->amount * 100;
            Log::info('Creating order for amount: ' . $request->amount);

        $order = $api->order->create([
            'amount' => $amount,
            'currency' => 'INR',
            'receipt' => 'order_' . time(),
            'payment_capture' => 1
        ]);

        Payment::create([
            'user_id' => auth::id(),
            'razorpay_order_id' => $order->id,
            'amount' => $amount,
            'currency' => 'INR',
            'status' => 'created'
        ]);

        return response()->json( [
        'id' => $order->id,
        'amount' => $request->amount * 100,
        'currency' => $order->currency]);

       }catch (\Exception $e) {
        Log::error('Order creation with transfers failed: ' . $e->getMessage());
        return response()->json(['error' => 'Payment failed'], 500);
    }
    }

    public function paymentSuccess(Request $request)
    {
        $request->validate([
            'razorpay_order_id' => 'required',
            'razorpay_payment_id' => 'required',
            'razorpay_signature' => 'required'
        ]);

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
