<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Success</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h3 class="text-center">Payment Successful</h3>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-success">
                            <p>Thank you for your payment!</p>
                            <p>Payment ID: {{ $payment->razorpay_payment_id }}</p>
                            <p>Amount: ₹{{ number_format($payment->amount, 2) }}</p>
                        </div>
                        <a href="/" class="btn btn-primary w-100">Back to Home</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>