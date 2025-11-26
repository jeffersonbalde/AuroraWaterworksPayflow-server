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

        try {
            // Find payment
            $payment = $this->findPayment($gateway, $paymentId, $sessionId, $reference);
            
            if (!$payment) {
                return redirect('/payment/failed')->with('error', 'Payment not found');
            }

            // For demo purposes, always show success
            return view('payment.success', compact('payment'));

        } catch (\Exception $e) {
            Log::error("Payment success handling failed: " . $e->getMessage());
            return redirect('/payment/failed')->with('error', 'Payment verification failed');
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

        return view('payment.cancelled');
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
     * Handle payment webhooks (stub for future use)
     */
    public function webhook(Request $request, $gateway)
    {
        Log::info("Webhook received from {$gateway} (Demo Mode)");
        return response()->json(['status' => 'webhook_received_demo_mode']);
    }

    private function findPayment($gateway, $paymentId, $sessionId, $reference)
    {
        if ($paymentId) {
            return Payment::find($paymentId);
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