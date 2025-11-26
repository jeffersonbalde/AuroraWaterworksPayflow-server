<!-- resources/views/payment/demo-success.blade.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demo Payment Successful - Aurora Waterworks</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .success-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .success-icon {
            font-size: 4rem;
            color: #28a745;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 10px 30px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card success-card">
                    <div class="card-body text-center p-5">
                        <div class="mb-4">
                            <i class="fas fa-check-circle success-icon"></i>
                        </div>
                        
                        <h2 class="card-title mb-3 text-success">Demo Payment Successful!</h2>
                        <p class="card-text text-muted mb-4">
                            This was a test payment. No real money was processed.
                        </p>

                        @if(isset($payment))
                        <div class="alert alert-info text-start mb-4">
                            <h6 class="alert-heading mb-3">Payment Details</h6>
                            <div class="row">
                                <div class="col-6">
                                    <strong>Reference Number:</strong><br>
                                    <code class="text-primary">{{ $payment->gateway_reference }}</code>
                                </div>
                                <div class="col-6">
                                    <strong>Amount Paid:</strong><br>
                                    <span class="fw-bold text-success">₱{{ number_format($payment->amount_paid, 2) }}</span>
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-6">
                                    <strong>Payment Date:</strong><br>
                                    {{ $payment->payment_date->format('M j, Y g:i A') }}
                                </div>
                                <div class="col-6">
                                    <strong>Gateway:</strong><br>
                                    <span class="badge bg-primary text-uppercase">{{ $payment->payment_gateway }}</span>
                                </div>
                            </div>
                            @if($payment->bill)
                            <div class="row mt-2">
                                <div class="col-12">
                                    <strong>Bill Period:</strong><br>
                                    {{ $payment->bill->reading_date->format('F Y') }}
                                </div>
                            </div>
                            @endif
                        </div>
                        @endif

                        <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                            <a href="/dashboard" class="btn btn-primary me-md-2">
                                <i class="fas fa-tachometer-alt me-2"></i>Return to Dashboard
                            </a>
                            <a href="/my-bills" class="btn btn-outline-secondary">
                                <i class="fas fa-file-invoice me-2"></i>View My Bills
                            </a>
                        </div>

                        <div class="mt-4">
                            <small class="text-muted">
                                <i class="fas fa-info-circle me-1"></i>
                                This is a demonstration. In a real scenario, you would be redirected to the actual payment gateway.
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>