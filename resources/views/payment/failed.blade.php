<!-- resources/views/payment/failed.blade.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Failed - Aurora Waterworks</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .failed-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .failed-icon {
            font-size: 4rem;
            color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card failed-card">
                    <div class="card-body text-center p-5">
                        <div class="mb-4">
                            <i class="fas fa-exclamation-triangle failed-icon"></i>
                        </div>
                        
                        <h2 class="card-title mb-3 text-danger">Payment Failed</h2>
                        <p class="card-text text-muted mb-4">
                            We encountered an issue while processing your payment. Please try again.
                        </p>

                        @if(session('error'))
                        <div class="alert alert-danger text-start mb-4">
                            <h6 class="alert-heading mb-2">
                                <i class="fas fa-bug me-2"></i>Error Details
                            </h6>
                            <p class="mb-0">{{ session('error') }}</p>
                        </div>
                        @endif

                        <div class="alert alert-info text-start mb-4">
                            <h6 class="alert-heading mb-2">
                                <i class="fas fa-lightbulb me-2"></i>Suggestions
                            </h6>
                            <ul class="mb-0 ps-3">
                                <li>Check your payment details and try again</li>
                                <li>Ensure you have sufficient balance</li>
                                <li>Verify your internet connection</li>
                                <li>Contact your bank if issues persist</li>
                            </ul>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                            <a href="/make-payment" class="btn btn-danger me-md-2">
                                <i class="fas fa-redo me-2"></i>Try Again
                            </a>
                            <a href="/dashboard" class="btn btn-outline-secondary">
                                <i class="fas fa-home me-2"></i>Dashboard
                            </a>
                        </div>

                        <div class="mt-4">
                            <small class="text-muted">
                                For assistance, contact support: support@aurorawater.com or call (02) 8-123-4567
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