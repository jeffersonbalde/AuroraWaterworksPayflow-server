<?php
// app/Http/Controllers/PaymentController.php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Handle successful payment
     */
    public function success(Request $request, $gateway = null)
    {
        $paymentId = $request->input('payment_id');
        $sessionId = $request->input('session_id');
        $reference = $request->input('reference');
        $payment_intent_id = $request->input('payment_intent_id');

        try {
            // Find payment
            $payment = $this->findPayment($gateway, $paymentId, $sessionId, $reference, $payment_intent_id);
            
            if (!$payment) {
                return redirect(config('app.frontend_url', 'http://localhost:5173') . '/payment/failed?error=payment_not_found');
            }

            // For PayMongo, check payment status
            if ($gateway === 'paymongo' || $payment->payment_gateway === 'gcash') {
                // Check payment status from PayMongo
                $status = $this->paymentService->checkPaymentStatus($payment);
                
                if ($status['status'] === 'completed') {
                    // Redirect to frontend success page
                    return redirect(config('payment.frontend_url', 'http://localhost:5173') . '/make-payment?success=true&payment_id=' . $payment->id);
                } else {
                    // Still pending, redirect to pending page
                    return redirect(config('payment.frontend_url', 'http://localhost:5173') . '/make-payment?pending=true&payment_id=' . $payment->id);
                }
            }

            // For demo purposes, always show success
            return redirect(config('payment.frontend_url', 'http://localhost:5173') . '/make-payment?success=true&payment_id=' . $payment->id);

        } catch (\Exception $e) {
            Log::error("Payment success handling failed: " . $e->getMessage());
            return redirect(config('payment.frontend_url', 'http://localhost:5173') . '/payment/failed?error=verification_failed');
        }
    }

    /**
     * Demo payment success page
     */
    public function demoSuccess($paymentId)
    {
        $payment = Payment::findOrFail($paymentId);
        
        return view('payment.demo-success', compact('payment'));
    }

    /**
     * Handle cancelled payment
     */
    public function cancel(Request $request, $gateway = null)
    {
        $paymentId = $request->input('payment_id');
        
        if ($paymentId) {
            $payment = Payment::find($paymentId);
            if ($payment) {
                $payment->update([
                    'payment_status' => 'cancelled',
                    'failure_reason' => 'User cancelled the payment',
                ]);
            }
        }

        return redirect(config('payment.frontend_url', 'http://localhost:5173') . '/make-payment?cancelled=true&payment_id=' . $paymentId);
    }

    /**
     * Show payment failed page
     */
    public function failed()
    {
        return view('payment.failed');
    }

    /**
     * Check payment status
     */
    public function checkStatus($paymentId)
    {
        $payment = Payment::findOrFail($paymentId);
        $status = $this->paymentService->checkPaymentStatus($payment);

        return response()->json([
            'success' => true,
            'payment_status' => $status['status'],
            'payment' => $payment
        ]);
    }

    /**
     * Handle payment webhooks
     */
    public function webhook(Request $request, $gateway)
    {
        try {
            if ($gateway === 'paymongo') {
                $payload = $request->all();
                $signature = $request->header('Paymongo-Signature');
                
                Log::info("PayMongo webhook received", [
                    'payload' => $payload,
                    'signature' => $signature
                ]);

                // Verify and process webhook
                $result = $this->paymentService->handlePaymongoWebhook($payload);
                
                if ($result) {
                    return response()->json(['status' => 'success'], 200);
                } else {
                    return response()->json(['status' => 'ignored'], 200);
                }
            }

            Log::info("Webhook received from {$gateway} (Unsupported)");
            return response()->json(['status' => 'unsupported_gateway'], 400);
        } catch (\Exception $e) {
            Log::error("Webhook processing error: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    private function findPayment($gateway, $paymentId, $sessionId, $reference, $paymentIntentId = null)
    {
        if ($paymentId) {
            return Payment::find($paymentId);
        }
        
        if ($paymentIntentId) {
            return Payment::where('gateway_reference', $paymentIntentId)->first();
        }
        
        if ($sessionId) {
            return Payment::where('gateway_reference', $sessionId)->first();
        }
        
        if ($reference) {
            return Payment::where('gateway_reference', $reference)->first();
        }

        return null;
    }
}