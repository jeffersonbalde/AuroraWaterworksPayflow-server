<?php
// config/payment.php

return [
    'default_gateway' => env('DEFAULT_PAYMENT_GATEWAY', 'demo'),
    
    'gateways' => [
        'paymongo' => [
            'secret_key' => env('PAYMONGO_SECRET_KEY'),
            'public_key' => env('PAYMONGO_PUBLIC_KEY'),
        ],
        'paypal' => [
            'client_id' => env('PAYPAL_CLIENT_ID'),
            'client_secret' => env('PAYPAL_CLIENT_SECRET'),
            'mode' => env('PAYPAL_MODE', 'sandbox'),
        ],
        'stripe' => [
            'secret_key' => env('STRIPE_SECRET_KEY'),
            'public_key' => env('STRIPE_PUBLIC_KEY'),
        ],
        'gcash' => [
            'merchant_id' => env('GCASH_MERCHANT_ID'),
            'secret_key' => env('GCASH_SECRET_KEY'),
        ],
    ],
    
    'success_url' => env('PAYMENT_SUCCESS_URL', '/payment/success'),
    'cancel_url' => env('PAYMENT_CANCEL_URL', '/payment/cancel'),
    'webhook_url' => env('PAYMENT_WEBHOOK_URL', '/api/payment/webhook'),
];