<h2>Payment Successful!</h2>
<p>Order ID: {{ $payment->razorpay_order_id }}</p>
<p>Payment ID: {{ $payment->razorpay_payment_id }}</p>
<p>Amount: ₹{{ $payment->amount }}</p>
