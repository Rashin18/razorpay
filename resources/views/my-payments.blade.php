<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Payments & Webhooks</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <ul class="nav nav-tabs mb-4" id="myTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="payments-tab" data-bs-toggle="tab" data-bs-target="#payments" type="button" role="tab">
                Payment Records
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="webhooks-tab" data-bs-toggle="tab" data-bs-target="#webhooks" type="button" role="tab">
                Webhook Events
            </button>
        </li>
    </ul>

    <div class="tab-content" id="myTabContent">
        <!-- Payments Tab -->
        <div class="tab-pane fade show active" id="payments" role="tabpanel">
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
                                <td>{{ $payment->created_at->setTimezone('Asia/Kolkata')->format('d M Y h:i A') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        <!-- Webhooks Tab -->
        <div class="tab-pane fade" id="webhooks" role="tabpanel">
            <h3 class="mb-4">Webhook Events</h3>
            @if ($webhookEvents->isEmpty())
                <p>No webhook events found.</p>
            @else
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Event ID</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Payment ID</th>
                            <th>Date</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($webhookEvents as $event)
                            <tr>
                                <td>{{ $event->id }}</td>
                                <td>{{ $event->event_type }}</td>
                                <td>
                                    @if($event->status === 'processed')
                                        <span class="badge bg-success">Processed</span>
                                    @elseif($event->status === 'failed')
                                        <span class="badge bg-danger">Failed</span>
                                    @else
                                        <span class="badge bg-warning text-dark">{{ ucfirst($event->status) }}</span>
                                    @endif
                                </td>
                                <td>{{ $event->entity_id ?? 'N/A' }}</td>
                                <td>{{ $event->created_at->setTimezone('Asia/Kolkata')->format('d M Y h:i A') }}</td>
                                <td>
                                    <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#eventModal-{{ $event->id }}">
                                        View
                                    </button>
                                </td>
                            </tr>
                            
                            <!-- Modal for each event -->
                            <div class="modal fade" id="eventModal-{{ $event->id }}" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Event Details #{{ $event->id }}</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                        <pre>@json($event->payload, JSON_PRETTY_PRINT)</pre>
                                    </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

    <div class="text-center mt-4">
        <a href="/" class="btn btn-primary">Make a New Payment</a>
    </div>
</div>

<!-- Include Bootstrap JS bundle for tab functionality -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>