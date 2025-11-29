<?php
// app/Http/Controllers/ClientController.php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\Payment;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ClientController extends Controller
{
    public function getBills(Request $request)
    {
        $user = Auth::user();

        $bills = Bill::where('user_id', $user->id)
            ->orderBy('reading_date', 'desc')
            ->get()
            ->map(function ($bill) {
                return [
                    'id' => $bill->id,
                    'reading_date' => $bill->reading_date->format('Y-m-d'),
                    'due_date' => $bill->due_date->format('Y-m-d'),
                    'previous_reading' => (float) $bill->previous_reading,
                    'present_reading' => (float) $bill->present_reading,
                    'consumption' => (float) $bill->consumption,
                    'amount' => (float) $bill->amount,
                    'penalty' => (float) $bill->penalty,
                    'total_payable' => (float) $bill->total_payable,
                    'status' => $bill->status,
                    'qr_number' => $bill->qr_number,
                    'is_overdue' => $bill->is_overdue,
                    'days_overdue' => $bill->days_overdue,
                    'meter_reader' => $bill->meter_reader,
                    'online_meter_used' => $bill->online_meter_used,
                ];
            });

        return response()->json([
            'success' => true,
            'bills' => $bills
        ]);
    }

    public function getPayments(Request $request)
    {
        $user = Auth::user();

        $payments = Payment::where('user_id', $user->id)
            ->with('bill')
            ->orderBy('payment_date', 'desc')
            ->get()
            ->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'bill_id' => $payment->bill_id,
                    'amount_paid' => (float) $payment->amount_paid,
                    'payment_date' => $payment->payment_date->format('Y-m-d\TH:i:s\Z'),
                    'qr_number' => $payment->qr_number,
                    'payment_method' => $payment->payment_method,
                    'status' => $payment->status,
                    'bill_period' => $payment->bill ? $payment->bill->reading_date->format('F Y') : 'N/A',
                    'electronic_qr_number' => $payment->electronic_qr_number,
                ];
            });

        return response()->json([
            'success' => true,
            'payments' => $payments
        ]);
    }

    public function getUsage(Request $request)
    {
        $user = Auth::user();

        $usage = Bill::where('user_id', $user->id)
            ->where('reading_date', '>=', now()->subMonths(12))
            ->orderBy('reading_date', 'asc')
            ->get()
            ->map(function ($bill) {
                return [
                    'month' => $bill->reading_date->format('M Y'),
                    'consumption' => (float) $bill->consumption,
                    'amount' => (float) $bill->amount,
                    'reading_date' => $bill->reading_date->format('Y-m-d'),
                ];
            });

        // Calculate statistics
        $stats = [
            'average_consumption' => $usage->avg('consumption') ?? 0,
            'total_consumption' => $usage->sum('consumption') ?? 0,
            'highest_consumption' => $usage->max('consumption') ?? 0,
            'total_amount' => $usage->sum('amount') ?? 0,
        ];

        return response()->json([
            'success' => true,
            'usage' => $usage,
            'stats' => $stats
        ]);
    }



    public function makePayment(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'bill_id' => 'required|exists:bills,id',
            'payment_method' => 'required|in:online,over_the_counter',
            'payment_gateway' => 'required|in:demo,paymongo,gcash',
            'amount' => 'required|numeric|min:0.01',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $bill = Bill::where('user_id', $user->id)
                ->where('id', $request->bill_id)
                ->firstOrFail();

            // Ensure the bill's automatic penalty and total payable are used
            // when validating and processing the payment.
            $effectivePenalty = $bill->automatic_penalty;
            $effectiveTotalPayable = $bill->automatic_total_payable;

            // Validate payment amount against the automatic total payable
            if ((float) $request->amount !== (float) $effectiveTotalPayable) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment amount must match the total payable amount'
                ], 422);
            }

            // Generate QR number based on payment method
            if ($request->payment_method === 'online') {
                $qrNumber = 'EQR' . date('YmdHis') . $user->id;
                $electronicQrNumber = 'ELEC' . uniqid();
            } else {
                $qrNumber = 'OTC' . date('YmdHis') . $user->id;
                $electronicQrNumber = null;
            }

            // Create payment record (initially pending)
            $payment = Payment::create([
                'user_id' => $user->id,
                'bill_id' => $bill->id,
                'wws_id' => $user->wws_id,
                'amount_paid' => $request->amount,
                'qr_number' => $qrNumber,
                'qr_date' => now(),
                'balance' => 0,
                'payment_method' => $request->payment_method,
                'payment_gateway' => $request->payment_gateway,
                'electronic_qr_number' => $electronicQrNumber,
                'electronic_amount' => $request->payment_method === 'online' ? $request->amount : null,
                'status' => 'completed', // Will be updated by gateway
                'payment_status' => 'pending', // Start as pending
                'payment_date' => now(),
            ]);

            // Process payment through gateway
            $paymentService = new PaymentService($request->payment_gateway);
            $paymentResult = $paymentService->processPayment($payment, $request->all());

            DB::commit();
            // Refresh payment data to get latest status
            $payment->refresh();

            // Prepare response
            $response = [
                'success' => true,
                'message' => $paymentResult['message'] ?? 'Payment processed successfully',
                'payment' => [
                    'id' => $payment->id,
                    'gateway_reference' => $payment->gateway_reference,
                    'payment_status' => $payment->payment_status,
                    'amount_paid' => $payment->amount_paid,
                    'payment_gateway' => $payment->payment_gateway,
                ],
                'payment_result' => $paymentResult,
            ];

            // If payment requires redirect (e.g., PayMongo checkout), include checkout URL
            if (isset($paymentResult['requires_redirect']) && $paymentResult['requires_redirect']) {
                $response['checkout_url'] = $paymentResult['checkout_url'];
                $response['requires_redirect'] = true;
            }

            return response()->json($response);
        } catch (\Exception $e) {
            DB::rollBack();

            // Provide more helpful error messages
            $errorMessage = $e->getMessage();
            if (strpos($errorMessage, 'gcash payment method is not allowed') !== false) {
                $errorMessage = 'GCash payment method is not enabled in your PayMongo account. Please enable GCash in your PayMongo dashboard settings.';
            }

            Log::error('Payment processing error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Payment processing failed',
                'error' => $errorMessage
            ], 500);
        }
    }

    public function updatePaymentReference(Request $request, $id)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'gateway_reference' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $payment = Payment::where('id', $id)
                ->where('user_id', $user->id)
                ->firstOrFail();

            // Update the gateway reference with customer-provided reference
            $payment->update([
                'gateway_reference' => $request->gateway_reference,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Reference number updated successfully',
                'payment' => [
                    'id' => $payment->id,
                    'gateway_reference' => $payment->gateway_reference,
                    'payment_status' => $payment->payment_status,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update payment reference: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update reference number',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getPendingBills(Request $request)
    {
        $user = Auth::user();

        $pendingBills = Bill::where('user_id', $user->id)
            ->where('status', 'pending')
            ->orderBy('due_date', 'asc')
            ->get()
            ->map(function ($bill) {
                // Apply automatic 10% penalty for overdue bills so the client
                // always sees the correct total payable.
                $effectivePenalty = $bill->automatic_penalty;
                $effectiveTotalPayable = $bill->automatic_total_payable;

                return [
                    'id' => $bill->id,
                    'reading_date' => $bill->reading_date->format('Y-m-d'),
                    'due_date' => $bill->due_date->format('Y-m-d'),
                    'previous_reading' => (float) $bill->previous_reading,
                    'present_reading' => (float) $bill->present_reading,
                    'consumption' => (float) $bill->consumption,
                    'amount' => (float) $bill->amount,
                    'penalty' => $effectivePenalty,
                    'total_payable' => $effectiveTotalPayable,
                    'is_overdue' => $bill->is_overdue,
                    'days_overdue' => $bill->days_overdue,
                ];
            });

        return response()->json([
            'success' => true,
            'pending_bills' => $pendingBills
        ]);
    }
}
