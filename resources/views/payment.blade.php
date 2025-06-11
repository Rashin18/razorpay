<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
        document.getElementById('payment-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const amount = document.getElementById('amount').value;
            
            fetch('/create-order', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                },
                body: JSON.stringify({ amount: amount })
            })
            .then(response => response.json())
            .then(order => {
                const options = {
                    key: '{{ config('razorpay.key') }}',
                    amount: order.amount,
                    currency: order.currency,
                    name: 'Your Company Name',
                    description: 'Test Payment',
                    order_id: order.id,
                    handler: function(response) {
                        // Submit the payment details to your server
                        fetch('/payment-success', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                            },
                            body: JSON.stringify({
                                razorpay_payment_id: response.razorpay_payment_id,
                                razorpay_order_id: response.razorpay_order_id,
                                razorpay_signature: response.razorpay_signature
                            })
                        })
                        .then(res => res.text())
                        .then(html => {
                            document.body.innerHTML = html;
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            window.location.href = '/payment-failure';
                        });
                    },
                    prefill: {
                        name: 'Customer Name',
                        email: 'customer@example.com',
                        contact: '9999999999'
                    },
                    notes: {
                        address: 'Customer Address'
                    },
                    theme: {
                        color: '#3399cc'
                    }
                };
                
                const rzp = new Razorpay(options);
                rzp.open();
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error creating order. Please try again.');
            });
        });
    </script>
</body>
</html>