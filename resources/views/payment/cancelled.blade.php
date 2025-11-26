<!-- resources/views/payment/cancelled.blade.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Cancelled - Aurora Waterworks</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #ff6b6b 0%, #feca57 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .cancelled-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .cancelled-icon {
            font-size: 4rem;
            color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card cancelled-card">
                    <div class="card-body text-center p-5">
                        <div class="mb-4">
                            <i class="fas fa-times-circle cancelled-icon"></i>
                        </div>
                        
                        <h2 class="card-title mb-3 text-danger">Payment Cancelled</h2>
                        <p class="card-text text-muted mb-4">
                            Your payment process was cancelled. No amount has been deducted from your account.
                        </p>

                        <div class="alert alert-warning text-start mb-4">
                            <h6 class="alert-heading mb-2">
                                <i class="fas fa-exclamation-triangle me-2"></i>Notice
                            </h6>
                            <p class="mb-0">
                                You can try again later or contact support if you need assistance with your payment.
                            </p>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                            <a href="/make-payment" class="btn btn-primary me-md-2">
                                <i class="fas fa-credit-card me-2"></i>Try Again
                            </a>
                            <a href="/dashboard" class="btn btn-outline-secondary">
                                <i class="fas fa-home me-2"></i>Return to Dashboard
                            </a>
                        </div>

                        <div class="mt-4">
                            <small class="text-muted">
                                Need help? Contact our support team at support@aurorawater.com
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