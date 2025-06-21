<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Success</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <div class="alert alert-success text-center">
        <h3>🎉 Payment Successful!</h3>
        <p>Thank you! Your payment was processed successfully.</p>
    </div>

    <div class="card">
        <div class="card-header">Payment Details</div>
        <div class="card-body">
            <p><strong>Order ID:</strong> {{ $payment->razorpay_order_id }}</p>
            <p><strong>Payment ID:</strong> {{ $payment->razorpay_payment_id }}</p>
            <p><strong>Amount:</strong> ₹{{ number_format($payment->amount / 100, 2) }}</p>
            <p><strong>Status:</strong> <span class="badge bg-success">{{ ucfirst($payment->status) }}</span></p>
        </div>
    </div>

    <div class="text-center mt-4">
        <a href="/" class="btn btn-primary">Make Another Payment</a>
        <a href="/my-payments" class="btn btn-secondary">My Payments</a>
    </div>
</div>
</body>
</html>

