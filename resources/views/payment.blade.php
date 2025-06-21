<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Make a Payment</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
</head>
<body>
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header text-center">
                    <h4>Make a Payment</h4>
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

    const amountInput = document.getElementById('amount').value;
    const amount = Number(amountInput);

    if (isNaN(amount) || amount < 1) {
        alert("Please enter a valid amount.");
        return;
    }

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
        const options = {
             key: '{{ env("RAZORPAY_KEY") }}',
    amount: order.amount, // ✅ keep in paise
    currency: order.currency,
    name: 'Razorpay App',
    description: 'Test Payment',
    order_id: order.id,
    handler: function (response) {
    fetch('/payment-success', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
            razorpay_order_id: response.razorpay_order_id,
            razorpay_payment_id: response.razorpay_payment_id,
            razorpay_signature: response.razorpay_signature
        })
    }).then(() => {
        window.location.href = '/payment-success';
    });
}


        };

        const rzp = new Razorpay(options);
        rzp.open();
    })
    .catch(error => {
        console.error("Order creation error:", error);
        alert("Something went wrong while initiating payment.");
    });
});
</script>
</body>
</html>


