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
                    <h4>💳 Make a Payment</h4>
                </div>
                <div class="card-body">
                    <form id="payment-form">
                        @csrf
                        <div class="mb-3">
                            <label for="amount" class="form-label">Amount (INR)</label>
                            <input type="number" step="0.01" class="form-control" id="amount" name="amount" min="1" required>
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

    const amountInput = document.getElementById('amount').value.trim();
    const amount = parseFloat(amountInput);

    if (isNaN(amount) || amount <= 0) {
        alert("Please enter a valid amount");
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
            key: 'rzp_test_Ra9XtlllX8fC3M', // Replace with your Razorpay key
            amount: order.amount, // in paise
            currency: order.currency,
            name: 'Razorpay',
            description: 'Payment Transaction',
            order_id: order.id,
            handler: function (response) {
                   response.amount = amount * 100; // Add amount in paise
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

        const rzp = new Razorpay(options);
        rzp.open();
    })
    .catch(error => {
        console.error("Error creating order:", error);
        alert("Something went wrong while creating order.");
    });
});
</script>
</body>
</html>

