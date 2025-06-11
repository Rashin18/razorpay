<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Failed</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-danger text-white">
                        <h3 class="text-center">Payment Failed</h3>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-danger">
                            <p>Your payment could not be processed.</p>
                            @if(session('error'))
                                <p>{{ session('error') }}</p>
                            @endif
                        </div>
                        <a href="/" class="btn btn-primary w-100">Try Again</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>