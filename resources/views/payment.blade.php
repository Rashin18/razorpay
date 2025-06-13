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
    document.getElementById('payment-form').addEventListener('submit', async function (e) {
    e.preventDefault();
    
    try {
        const amount = parseFloat(document.getElementById('amount').value);
        console.log('Attempting payment with amount:', amount);

        const response = await fetch('/create-order', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ amount: amount })
        });

        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.message || 'Payment failed');
        }

        const order = await response.json();
        console.log('Order created:', order);

        const options = {
            key: 'rzp_test_uLGlQp5vZDcWTf',
            amount: order.amount,
            currency: order.currency,
            name: 'Your Company Name',
            order_id: order.id,
            handler: async function (response) {
                try {
                    const paymentResponse = await fetch('/payment-success', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify(response)
                    });
                    const html = await paymentResponse.text();
                    document.write(html);
                } catch (error) {
                    console.error('Payment success handling failed:', error);
                    alert('Payment verification failed. Please contact support.');
                }
            }
        };

        new Razorpay(options).open();
    } catch (error) {
        console.error('Payment error:', error);
        alert(`Payment failed: ${error.message}`);
    }
});
    </script>
</body>
</html>

