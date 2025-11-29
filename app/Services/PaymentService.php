<?php
// app/Services/PaymentService.php - REAL PAYMONGO GCASH INTEGRATION

namespace App\Services;

use App\Models\Payment;
use App\Models\Bill;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    private $gateway;
    private $paymongoBaseUrl = 'https://api.paymongo.com/v1';
    
    public function __construct($gateway = null)
    {
        $this->gateway = $gateway ?? config('payment.default_gateway', 'demo');
    }

    public function processPayment(Payment $payment, array $paymentData = [])
    {
        try {
            // Check if we're in test mode - if so, always use demo payment
            $mode = config('payment.paymongo.mode', 'test');
            if ($mode === 'test') {
                Log::info('Using demo payment mode (test mode)', [
                    'payment_id' => $payment->id,
                    'gateway' => $this->gateway
                ]);
                return $this->processDemoPayment($payment, $paymentData, $this->gateway === 'gcash' ? 'gcash' : 'demo');
            }

            // If gateway is not demo and API keys are not configured, fallback to demo
            if ($this->gateway !== 'demo' && !$this->isGatewayConfigured($this->gateway)) {
                Log::warning("Gateway {$this->gateway} not configured, falling back to demo");
                $this->gateway = 'demo';
            }

            switch ($this->gateway) {
                case 'paymongo':
                case 'gcash':
                    return $this->processViaGCash($payment, $paymentData);
                case 'demo': // For testing without real payment
                default:
                    return $this->processDemoPayment($payment, $paymentData);
            }
        } catch (\Exception $e) {
            Log::error("Payment processing failed: " . $e->getMessage());
            Log::error("Stack trace: " . $e->getTraceAsString());
            throw $e; // Re-throw to let controller handle it
        }
    }

    private function isGatewayConfigured($gateway)
    {
        if ($gateway === 'gcash' || $gateway === 'paymongo') {
            $secretKey = $this->getPaymongoSecretKey();
            $publicKey = $this->getPaymongoPublicKey();
            return !empty($secretKey) && !empty($publicKey);
        }
        return false;
    }

    private function processViaGCash(Payment $payment, array $paymentData)
    {
        try {
            $user = $payment->user;
            $bill = $payment->bill;
            
            // Get current mode
            $mode = config('payment.paymongo.mode', 'live');
            
            // Get PayMongo API keys
            $secretKey = $this->getPaymongoSecretKey();
            
            if (empty($secretKey)) {
                throw new \Exception('PayMongo API keys not configured');
            }
            
            // Log which mode we're using for this payment
            Log::info('Processing GCash payment', [
                'payment_id' => $payment->id,
                'mode' => $mode,
                'amount' => $payment->amount_paid,
                'is_live' => $mode === 'live'
            ]);

            // Create payment intent with GCash
            $amountInCents = (int)($payment->amount_paid * 100); // Convert to cents
            
            // Prepare line items for PayMongo
            $lineItems = [
                [
                    'name' => "Water Bill - " . date('F Y', strtotime($bill->reading_date)),
                    'quantity' => 1,
                    'amount' => $amountInCents,
                    'currency' => 'PHP'
                ]
            ];

            // Create payment intent
            // Note: PayMongo metadata must be flat key-value pairs with string values
            // Note: Don't restrict payment_method_allowed - let PayMongo handle it when we attach GCash
            $paymentIntentData = [
                'data' => [
                    'attributes' => [
                        'amount' => $amountInCents,
                        'currency' => 'PHP',
                        // Removed payment_method_allowed - will be set when attaching GCash payment method
                        'description' => "Water Bill Payment - {$user->name} - " . date('F Y', strtotime($bill->reading_date)),
                        'statement_descriptor' => 'AURORA WATERWORKS',
                        'metadata' => [
                            'payment_id' => (string)$payment->id,
                            'bill_id' => (string)$bill->id,
                            'user_id' => (string)$user->id,
                            'wws_id' => $user->wws_id ? (string)$user->wws_id : '',
                        ]
                    ]
                ]
            ];

            Log::info('Creating PayMongo payment intent', [
                'payment_id' => $payment->id,
                'amount' => $amountInCents
            ]);

            // Make API call to PayMongo to create payment intent
            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . base64_encode($secretKey . ':'),
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post($this->paymongoBaseUrl . '/payment_intents', $paymentIntentData);

            if (!$response->successful()) {
                $errorBody = $response->json();
                Log::error('PayMongo payment intent creation failed', [
                    'status' => $response->status(),
                    'response' => $errorBody
                ]);
                throw new \Exception('Failed to create payment intent: ' . ($errorBody['errors'][0]['detail'] ?? 'Unknown error'));
            }

            $paymentIntent = $response->json()['data'];
            $paymentIntentId = $paymentIntent['id'];

            // Create payment method for GCash with payment intent ID
            $paymentMethodData = [
                'data' => [
                    'attributes' => [
                        'type' => 'gcash',
                        'billing' => [
                            'name' => $user->name,
                            'email' => $user->email,
                            'phone' => $user->contact_number ?? null,
                        ]
                    ]
                ]
            ];

            $methodResponse = Http::withHeaders([
                'Authorization' => 'Basic ' . base64_encode($secretKey . ':'),
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post($this->paymongoBaseUrl . '/payment_methods', $paymentMethodData);

            if (!$methodResponse->successful()) {
                $errorBody = $methodResponse->json();
                Log::error('PayMongo payment method creation failed', [
                    'status' => $methodResponse->status(),
                    'response' => $errorBody,
                    'payment_id' => $payment->id
                ]);
                $errorMessage = 'Failed to create payment method';
                if (isset($errorBody['errors']) && is_array($errorBody['errors']) && count($errorBody['errors']) > 0) {
                    $errorMessage .= ': ' . ($errorBody['errors'][0]['detail'] ?? $errorBody['errors'][0]['message'] ?? 'Unknown error');
                }
                throw new \Exception($errorMessage);
            }

            $paymentMethod = $methodResponse->json()['data'];
            $paymentMethodId = $paymentMethod['id'];

            // Attach payment method to payment intent
            // This will automatically set the payment method type and generate checkout URL
            $attachResponse = Http::withHeaders([
                'Authorization' => 'Basic ' . base64_encode($secretKey . ':'),
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post($this->paymongoBaseUrl . "/payment_intents/{$paymentIntentId}/attach", [
                'data' => [
                    'attributes' => [
                        'payment_method' => $paymentMethodId,
                        'return_url' => config('app.url') . '/payment/success/paymongo?payment_id=' . $payment->id,
                    ]
                ]
            ]);

            if (!$attachResponse->successful()) {
                $errorBody = $attachResponse->json();
                Log::error('PayMongo payment method attach failed', [
                    'status' => $attachResponse->status(),
                    'response' => $errorBody,
                    'payment_id' => $payment->id
                ]);
                $errorMessage = 'Failed to attach payment method';
                if (isset($errorBody['errors']) && is_array($errorBody['errors']) && count($errorBody['errors']) > 0) {
                    $errorMessage .= ': ' . ($errorBody['errors'][0]['detail'] ?? $errorBody['errors'][0]['message'] ?? 'Unknown error');
                }
                throw new \Exception($errorMessage);
            }

            $attachedData = $attachResponse->json()['data'];
            $checkoutUrl = null;

            // Get the next action URL (checkout URL) from the attached payment intent
            if (isset($attachedData['attributes']['next_action'])) {
                $nextAction = $attachedData['attributes']['next_action'];
                if (isset($nextAction['redirect']['url'])) {
                    $checkoutUrl = $nextAction['redirect']['url'];
                }
            }
            
            // If no checkout URL in next_action, check if there's a checkout_url in the response
            if (!$checkoutUrl && isset($attachedData['attributes']['checkout_url'])) {
                $checkoutUrl = $attachedData['attributes']['checkout_url'];
            }

            // If still no checkout URL, try to retrieve the payment intent again
            if (empty($checkoutUrl)) {
                Log::warning('PayMongo checkout URL not found in attach response, retrieving payment intent', [
                    'payment_id' => $payment->id,
                    'payment_intent_id' => $paymentIntentId
                ]);
                
                // Retrieve the payment intent to get the latest status
                $retrieveResponse = Http::withHeaders([
                    'Authorization' => 'Basic ' . base64_encode($secretKey . ':'),
                    'Accept' => 'application/json',
                ])->get($this->paymongoBaseUrl . "/payment_intents/{$paymentIntentId}");

                if ($retrieveResponse->successful()) {
                    $retrievedData = $retrieveResponse->json()['data'];
                    if (isset($retrievedData['attributes']['next_action']['redirect']['url'])) {
                        $checkoutUrl = $retrievedData['attributes']['next_action']['redirect']['url'];
                    }
                }
            }

            // Validate checkout URL exists
            if (empty($checkoutUrl)) {
                Log::error('PayMongo checkout URL not found after all attempts', [
                    'payment_id' => $payment->id,
                    'payment_intent_id' => $paymentIntentId,
                    'attach_response' => $attachedData,
                    'payment_intent_status' => $attachedData['attributes']['status'] ?? 'unknown'
                ]);
                throw new \Exception('Failed to generate payment checkout URL. The payment intent was created but the checkout URL is not available. Please contact support with payment ID: ' . $payment->id);
            }

            // Update payment record
            $payment->update([
                'gateway_reference' => $paymentIntentId,
                'gateway_transaction_id' => $paymentMethodId,
                'payment_gateway' => 'gcash',
                'payment_status' => 'pending',
                'gateway_response' => json_encode($attachedData),
            ]);

            Log::info('PayMongo payment intent created successfully', [
                'payment_id' => $payment->id,
                'payment_intent_id' => $paymentIntentId,
                'checkout_url' => $checkoutUrl
            ]);

            return [
                'success' => true,
                'payment_intent_id' => $paymentIntentId,
                'checkout_url' => $checkoutUrl,
                'reference' => $paymentIntentId,
                'gateway' => 'gcash',
                'message' => 'Payment link created. Please complete payment via GCash.',
                'requires_redirect' => true,
            ];

        } catch (\Exception $e) {
            Log::error('GCash payment processing error', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Update payment status to failed
            $payment->update([
                'payment_status' => 'failed',
                'failure_reason' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function processDemoPayment(Payment $payment, array $paymentData, $gateway = 'demo')
    {
        // For GCash QR code payment
        if ($gateway === 'gcash') {
            // Don't auto-generate gateway_reference - customer will provide it after payment
            $payment->update([
                'payment_gateway' => 'gcash',
                'payment_status' => 'pending', // Keep as pending until admin confirms
            ]);

            return [
                'success' => true,
                'gateway' => 'gcash',
                'message' => 'Please scan the QR code and complete payment via GCash',
                'requires_redirect' => false,
                'payment_method' => 'qr_code',
                'qr_code_url' => '/assets/admin_gcash_qrcode.jpg', // Path to QR code image (in public/assets)
            ];
        }

        // For other demo payments
        $reference = strtoupper($gateway) . date('YmdHis') . $payment->id;
        
        $payment->update([
            'gateway_reference' => $reference,
            'payment_gateway' => $gateway,
            'payment_status' => 'processing',
        ]);

        // Simulate API delay
        sleep(1);

        // Complete the payment
        $this->completePayment($payment);

        $gatewayMessages = [
            'demo' => 'Demo payment processed successfully',
        ];

        return [
            'success' => true,
            'reference' => $reference,
            'gateway' => $gateway,
            'message' => $gatewayMessages[$gateway] ?? 'Payment processed successfully',
            'requires_redirect' => false,
        ];
    }

    public function verifyPaymongoWebhook($payload, $signature)
    {
        // Verify webhook signature if needed
        // For now, we'll trust the webhook
        return true;
    }

    public function handlePaymongoWebhook($webhookData)
    {
        try {
            $eventType = $webhookData['data']['attributes']['type'] ?? null;
            $eventData = $webhookData['data']['attributes']['data'] ?? null;

            Log::info('PayMongo webhook received', [
                'event_type' => $eventType,
                'data' => $eventData
            ]);

            if ($eventType === 'payment.paid' || $eventType === 'payment_intent.succeeded') {
                $paymentIntentId = $eventData['id'] ?? null;
                
                if (!$paymentIntentId) {
                    Log::warning('Payment intent ID not found in webhook');
                    return false;
                }

                // Find payment by gateway reference
                $payment = Payment::where('gateway_reference', $paymentIntentId)->first();

                if (!$payment) {
                    Log::warning('Payment not found for payment intent', [
                        'payment_intent_id' => $paymentIntentId
                    ]);
                    return false;
                }

                // Complete the payment
                $this->completePayment($payment);

                Log::info('Payment completed via webhook', [
                    'payment_id' => $payment->id,
                    'payment_intent_id' => $paymentIntentId
                ]);

                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::error('Webhook processing error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    public function checkPaymongoPaymentStatus(Payment $payment)
    {
        try {
            $secretKey = $this->getPaymongoSecretKey();
            
            if (empty($secretKey) || empty($payment->gateway_reference)) {
                return [
                    'status' => $payment->payment_status,
                    'gateway' => 'gcash',
                    'reference' => $payment->gateway_reference,
                ];
            }

            // Check payment intent status
            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . base64_encode($secretKey . ':'),
                'Accept' => 'application/json',
            ])->get($this->paymongoBaseUrl . '/payment_intents/' . $payment->gateway_reference);

            if ($response->successful()) {
                $paymentIntent = $response->json()['data'];
                $status = $paymentIntent['attributes']['status'] ?? 'pending';

                // Map PayMongo status to our status
                $mappedStatus = 'pending';
                if ($status === 'succeeded' || $status === 'paid') {
                    $mappedStatus = 'completed';
                    // If payment is completed, update our records
                    if ($payment->payment_status !== 'completed') {
                        $this->completePayment($payment);
                    }
                } elseif ($status === 'failed' || $status === 'canceled') {
                    $mappedStatus = 'failed';
                    if ($payment->payment_status !== 'failed') {
                        $payment->update([
                            'payment_status' => 'failed',
                            'failure_reason' => 'Payment failed or cancelled',
                        ]);
                    }
                }

                return [
                    'status' => $mappedStatus,
                    'gateway_status' => $status,
                    'gateway' => 'gcash',
                    'reference' => $payment->gateway_reference,
                ];
            }

            return [
                'status' => $payment->payment_status,
                'gateway' => 'gcash',
                'reference' => $payment->gateway_reference,
            ];
        } catch (\Exception $e) {
            Log::error('Error checking PayMongo payment status', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage()
            ]);

            return [
                'status' => $payment->payment_status,
                'gateway' => 'gcash',
                'reference' => $payment->gateway_reference,
            ];
        }
    }

    private function completePayment(Payment $payment)
    {
        DB::transaction(function () use ($payment) {
            $payment->update([
                'payment_status' => 'completed',
                'processed_at' => now(),
            ]);

            $payment->bill->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);
        });
    }

    public function checkPaymentStatus(Payment $payment)
    {
        if ($payment->payment_gateway === 'gcash' || $payment->payment_gateway === 'paymongo') {
            return $this->checkPaymongoPaymentStatus($payment);
        }

        return [
            'status' => $payment->payment_status,
            'gateway' => $payment->payment_gateway,
            'reference' => $payment->gateway_reference,
        ];
    }

    private function getPaymongoSecretKey()
    {
        // Get mode from config (default is 'live' as set in config/payment.php)
        $mode = config('payment.paymongo.mode', 'live');
        
        // Log which mode is being used
        Log::info('PayMongo mode check', [
            'mode' => $mode,
            'env_value' => env('PAYMONGO_MODE', 'not_set'),
            'config_default' => config('payment.paymongo.mode')
        ]);
        
        if ($mode === 'live') {
            $key = config('payment.gateways.paymongo.secret_key_live');
            Log::info('Using LIVE PayMongo secret key', [
                'key_prefix' => substr($key, 0, 10) . '...'
            ]);
        } else {
            $key = config('payment.gateways.paymongo.secret_key_test');
            Log::info('Using TEST PayMongo secret key', [
                'key_prefix' => substr($key, 0, 10) . '...'
            ]);
        }
        
        // Log if key is missing for debugging
        if (empty($key)) {
            Log::warning('PayMongo secret key is empty', [
                'mode' => $mode,
                'live_key_set' => !empty(config('payment.gateways.paymongo.secret_key_live')),
                'test_key_set' => !empty(config('payment.gateways.paymongo.secret_key_test'))
            ]);
        }
        
        return $key;
    }

    private function getPaymongoPublicKey()
    {
        // Get mode from config (default is 'live' as set in config/payment.php)
        $mode = config('payment.paymongo.mode', 'live');
        
        if ($mode === 'live') {
            return config('payment.gateways.paymongo.public_key_live');
        }
        return config('payment.gateways.paymongo.public_key_test');
    }
}