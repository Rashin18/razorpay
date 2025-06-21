<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Payments</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h3 class="mb-4">My Payments</h3>
    @if ($payments->isEmpty())
        <p>No payments found.</p>
    @else
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Payment ID</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Currency</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($payments as $payment)
                    <tr>
                        <td>{{ $payment->razorpay_order_id }}</td>
                        <td>{{ $payment->razorpay_payment_id ?? 'N/A' }}</td>
                        <td>₹{{ number_format($payment->amount / 100, 2) }}</td>
                        <td>
                            @if($payment->status === 'success')
                                <span class="badge bg-success">Success</span>
                            @elseif($payment->status === 'failed')
                                <span class="badge bg-danger">Failed</span>
                            @else
                                <span class="badge bg-secondary">{{ ucfirst($payment->status) }}</span>
                            @endif
                        </td>
                        <td>{{ $payment->currency }}</td>
                        <td>{{ $payment->created_at->format('d M Y h:i A') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="text-center">
        <a href="/" class="btn btn-primary">Make a New Payment</a>
    </div>
</div>
</body>
</html>
