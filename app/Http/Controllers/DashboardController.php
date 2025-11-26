<?php
// app/Http/Controllers/DashboardController.php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Bill;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function getClientDashboardData()
    {
        $user = Auth::user();

        if (!$user || $user->role !== 'client') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        try {
            // Get basic client info
            $clientInfo = [
                'name' => $user->name,
                'wws_id' => $user->wws_id,
                'address' => $user->address,
                'service_type' => $user->service ? strtoupper($user->service) : 'RESIDENTIAL',
                'account_status' => $user->status === 'active' ? 'Active' : ucfirst($user->status),
            ];

            // Get current balance (sum of pending bills)
            $currentBalance = Bill::where('user_id', $user->id)
                ->where('status', 'pending')
                ->sum('total_payable');

            // Get last payment
            $lastPayment = Payment::where('user_id', $user->id)
                ->where('payment_status', 'completed')
                ->orderBy('payment_date', 'desc')
                ->first();

            // Get recent bills (last 6 months)
            $recentBills = Bill::where('user_id', $user->id)
                ->where('reading_date', '>=', now()->subMonths(6))
                ->orderBy('reading_date', 'desc')
                ->get()
                ->map(function ($bill) {
                    return [
                        'id' => $bill->id,
                        'date' => $bill->reading_date->format('d/m/Y'),
                        'consumption' => $bill->consumption . ' m³',
                        'amount' => '₱' . number_format($bill->total_payable, 2),
                        'due_date' => $bill->due_date->format('d/m/Y'),
                        'status' => $bill->status,
                        'is_overdue' => $bill->is_overdue,
                    ];
                });

            // Get consumption history for chart (last 6 months)
            $consumptionHistory = Bill::where('user_id', $user->id)
                ->where('reading_date', '>=', now()->subMonths(6))
                ->orderBy('reading_date', 'asc')
                ->get()
                ->map(function ($bill) {
                    return [
                        'month' => $bill->reading_date->format('M Y'),
                        'consumption' => (float) $bill->consumption,
                        'amount' => (float) $bill->total_payable,
                        'reading_date' => $bill->reading_date->format('Y-m-d'),
                    ];
                });

            // Calculate statistics
            $totalBillsThisYear = Bill::where('user_id', $user->id)
                ->whereYear('reading_date', date('Y'))
                ->count();

            $paidBillsCount = Bill::where('user_id', $user->id)
                ->where('status', 'paid')
                ->count();

            $totalBillsCount = Bill::where('user_id', $user->id)->count();
            
            $paymentHistoryPercentage = $totalBillsCount > 0 
                ? round(($paidBillsCount / $totalBillsCount) * 100) 
                : 100;

            // Calculate average consumption
            $avgConsumption = Bill::where('user_id', $user->id)
                ->where('reading_date', '>=', now()->subMonths(6))
                ->avg('consumption');

            $monthlyConsumption = Bill::where('user_id', $user->id)
                ->whereYear('reading_date', date('Y'))
                ->whereMonth('reading_date', date('m'))
                ->value('consumption');

            // Get next reading date (next month same day as last reading)
            $lastBill = Bill::where('user_id', $user->id)
                ->orderBy('reading_date', 'desc')
                ->first();

            $nextReading = $lastBill 
                ? $lastBill->reading_date->addMonth()->format('d/m/Y')
                : now()->addMonth()->format('d/m/Y');

            // Generate account alerts
            $accountAlerts = $this->generateAccountAlerts($user, $currentBalance, $recentBills);

            return response()->json([
                'success' => true,
                'data' => [
                    'client_info' => $clientInfo,
                    'stats' => [
                        'current_balance' => (float) $currentBalance,
                        'monthly_consumption' => $monthlyConsumption ? $monthlyConsumption . ' m³' : '0 m³',
                        'avg_consumption' => $avgConsumption ? round($avgConsumption, 1) . ' m³' : '0 m³',
                        'payment_history' => $paymentHistoryPercentage . '%',
                        'next_reading' => $nextReading,
                        'last_payment' => $lastPayment ? $lastPayment->payment_date->format('d/m/Y') : 'No payments yet',
                        'total_bills_this_year' => $totalBillsThisYear,
                    ],
                    'recent_bills' => $recentBills,
                    'consumption_history' => $consumptionHistory,
                    'account_alerts' => $accountAlerts,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch dashboard data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function generateAccountAlerts($user, $currentBalance, $recentBills)
    {
        $alerts = [];

        // Check for overdue bills
        $overdueBills = collect($recentBills)->where('is_overdue', true)->count();
        if ($overdueBills > 0) {
            $alerts[] = [
                'type' => 'danger',
                'message' => 'Overdue bills detected',
                'details' => "You have {$overdueBills} overdue bill(s). Please pay immediately.",
            ];
        }

        // Check current balance
        if ($currentBalance > 0) {
            $alerts[] = [
                'type' => 'warning',
                'message' => 'Pending balance',
                'details' => 'You have outstanding bills that need payment.',
            ];
        } else {
            $alerts[] = [
                'type' => 'success',
                'message' => 'Account in good standing',
                'details' => 'No pending balances',
            ];
        }

        // Next reading alert
        $alerts[] = [
            'type' => 'info',
            'message' => 'Next meter reading',
            'details' => 'Scheduled for next month',
        ];

        // Consumption alert (if we have consumption data)
        if (count($recentBills) > 1) {
            $currentConsumption = $recentBills[0]['consumption'] ?? 0;
            $previousConsumption = $recentBills[1]['consumption'] ?? 0;
            
            // Extract numeric value from string (e.g., "35 m³" -> 35)
            $current = floatval($currentConsumption);
            $previous = floatval($previousConsumption);
            
            if ($previous > 0 && $current > $previous) {
                $increase = (($current - $previous) / $previous) * 100;
                if ($increase > 10) {
                    $alerts[] = [
                        'type' => 'warning',
                        'message' => 'Consumption alert',
                        'details' => round($increase) . '% higher than last month',
                    ];
                }
            }
        }

        return $alerts;
    }
}