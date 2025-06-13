<h2>My Payments</h2>
<table border="1">
    <tr>
        <th>Order ID</th>
        <th>Payment ID</th>
        <th>Status</th>
        <th>Amount</th>
        <th>Date</th>
    </tr>
    @foreach($payments as $payment)
        <tr>
            <td>{{ $payment->razorpay_order_id }}</td>
            <td>{{ $payment->razorpay_payment_id ?? '-' }}</td>
            <td>{{ $payment->status }}</td>
            <td>₹{{ $payment->amount }}</td>
            <td>{{ $payment->created_at }}</td>
        </tr>
    @endforeach
</table>
