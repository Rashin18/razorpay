<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
</head>
<body>
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="text-center">Make a Payment</h3>
                </div>
                <div class="card-body">
                    <form id="payment-form">
                        @csrf
                        <div class="mb-3">
                            <label for="amount" class="form-label">Amount (INR)</label>
                            <input type="number" class="form-control" id="amount" name="amount" min="1" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Pay Now</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('payment-form').addEventListener('submit', function (e) {
        e.preventDefault();
        let amount = parseFloat(document.getElementById('amount').value); // converts to float

fetch('/create-order', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    },
    body: JSON.stringify({ amount: amount })
 
})

        .then(res => res.json())
            .then(order => {
                let options = {
                    key: 'rzp_test_uLGlQp5vZDcWTf',
                    amount: order.amount,
                    currency: order.currency,
                    name: 'Razorpay App',
                    order_id: order.id,
                    handler: function (response) {
                        fetch('/payment-success', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify(response)
                        })
                        .then(res => res.text())
                        .then(html => document.write(html));
                    }
                };
                new Razorpay(options).open();
            });
        });
    </script>
</body>
</html>

