<?php
// app/Http/Controllers/AdminController.php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\User;
use App\Models\Payment;
use App\Models\AuthorizationCode;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdminController extends Controller
{
    public function getUsers(Request $request)
    {
        // Allow both admin and staff to manage approvals
        if (!Auth::user()->isAdmin() && !Auth::user()->isStaff()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $users = User::orderBy('created_at', 'desc')->get();

        return response()->json([
            'users' => $users
        ]);
    }

    public function approveUser(Request $request, $id)
    {
        if (!Auth::user()->isAdmin() && !Auth::user()->isStaff()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $user = User::findOrFail($id);

        // Allow approval of pending OR rejected users
        if ($user->status !== 'pending' && $user->status !== 'rejected') {
            return response()->json([
                'message' => 'User is not pending approval or rejected'
            ], 400);
        }

        $user->update([
            'status' => 'active',
            'approved_by' => Auth::user()->name,
            'approved_at' => now(),
            'rejected_by' => null,
            'rejected_at' => null,
            'rejection_reason' => null,
        ]);

        return response()->json([
            'message' => 'User approved successfully',
            'user' => $user
        ]);
    }


    public function rejectUser(Request $request, $id)
    {
        if (!Auth::user()->isAdmin() && !Auth::user()->isStaff()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'rejection_reason' => 'required|string|max:500'
        ]);

        $user = User::findOrFail($id);

        // Only allow rejection of pending users
        if ($user->status !== 'pending') {
            return response()->json([
                'message' => 'User is not pending approval'
            ], 400);
        }

        $user->update([
            'status' => 'rejected',
            'rejected_by' => Auth::user()->name,
            'rejected_at' => now(),
            'rejection_reason' => $request->rejection_reason,
            'approved_by' => null,
            'approved_at' => null,
        ]);

        return response()->json([
            'message' => 'User rejected successfully',
            'user' => $user
        ]);
    }

    public function createStaff(Request $request)
    {
        if (!Auth::user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'contact_number' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'position' => 'required|string|max:255',
            'staff_notes' => 'nullable|string|max:1000',
            'password' => 'required|string|min:8|confirmed',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // 5MB max
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $userData = [
                'name' => $request->name,
                'email' => $request->email,
                'contact_number' => $request->contact_number,
                'address' => $request->address,
                'position' => $request->position,
                'staff_notes' => $request->staff_notes,
                'role' => 'staff',
                'status' => 'active', // Auto-approve staff created by admin
                'approved_by' => Auth::user()->name,
                'approved_at' => now(),
                'password' => Hash::make($request->password),
                'created_by' => Auth::user()->name,
            ];

            // Handle avatar upload
            if ($request->hasFile('avatar') && $request->file('avatar')->isValid()) {
                $avatarPath = $request->file('avatar')->store('avatars', 'public');
                $userData['avatar'] = $avatarPath;
            }

            $user = User::create($userData);

            return response()->json([
                'message' => 'Staff created successfully',
                'user' => $user
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create staff',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateStaff(Request $request, $id)
    {
        if (!Auth::user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $user = User::findOrFail($id);

        // Ensure we're only updating staff users
        if ($user->role !== 'staff') {
            return response()->json(['message' => 'Can only update staff users'], 400);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $id,
            'contact_number' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'position' => 'required|string|max:255',
            'staff_notes' => 'nullable|string|max:1000',
            'password' => 'nullable|string|min:8|confirmed',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // 5MB max
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $updateData = [
                'name' => $request->name,
                'email' => $request->email,
                'contact_number' => $request->contact_number,
                'address' => $request->address,
                'position' => $request->position,
                'staff_notes' => $request->staff_notes,
            ];

            // Only update password if provided
            if ($request->password) {
                $updateData['password'] = Hash::make($request->password);
            }

            // Handle avatar upload
            if ($request->hasFile('avatar') && $request->file('avatar')->isValid()) {
                // Delete old avatar if exists
                if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                    Storage::disk('public')->delete($user->avatar);
                }

                $avatarPath = $request->file('avatar')->store('avatars', 'public');
                $updateData['avatar'] = $avatarPath;
            }

            $user->update($updateData);

            return response()->json([
                'message' => 'Staff updated successfully',
                'user' => $user
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update staff',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function deleteStaff($id)
    {
        if (!Auth::user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $user = User::findOrFail($id);

        // Prevent admin from deleting themselves
        if ($user->id === Auth::user()->id) {
            return response()->json([
                'message' => 'You cannot delete your own account'
            ], 400);
        }

        // Ensure we're only deleting staff users
        if ($user->role !== 'staff') {
            return response()->json(['message' => 'Can only delete staff users'], 400);
        }

        try {
            // Delete avatar file if exists
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }

            $user->delete();

            return response()->json([
                'message' => 'Staff deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete staff',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Add these methods to your AdminController
    public function deactivateStaff(Request $request, $id)
    {
        if (!Auth::user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $user = User::findOrFail($id);

        // Ensure we're only deactivating staff users
        if ($user->role !== 'staff') {
            return response()->json(['message' => 'Can only deactivate staff users'], 400);
        }

        $request->validate([
            'deactivate_reason' => 'required|string|max:500'
        ]);

        try {
            $user->update([
                'status' => 'inactive',
                'deactivate_reason' => $request->deactivate_reason,
                'deactivated_at' => now(),
                'deactivated_by' => Auth::user()->name,
            ]);

            return response()->json([
                'message' => 'Staff deactivated successfully',
                'user' => $user
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to deactivate staff',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function activateStaff(Request $request, $id)
    {
        if (!Auth::user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $user = User::findOrFail($id);

        // Ensure we're only activating staff users
        if ($user->role !== 'staff') {
            return response()->json(['message' => 'Can only activate staff users'], 400);
        }

        try {
            $user->update([
                'status' => 'active',
                'deactivate_reason' => null,
                'deactivated_at' => null,
                'deactivated_by' => null,
            ]);

            return response()->json([
                'message' => 'Staff activated successfully',
                'user' => $user
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to activate staff',
                'error' => $e->getMessage()
            ], 500);
        }
    }





    // Add these methods to your AdminController.php

    public function getCustomers(Request $request)
    {
        if (!Auth::user()->isAdmin() && !Auth::user()->isStaff()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Get only client users (customers)
        $customers = User::where('role', 'client')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'customers' => $customers
        ]);
    }

    public function updateCustomer(Request $request, $id)
    {
        if (!Auth::user()->isAdmin() && !Auth::user()->isStaff()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $customer = User::where('role', 'client')->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'contact_number' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'wws_id' => 'nullable|string|max:50|unique:users,wws_id,' . $id,
            'service' => 'required|in:residential,commercial,institutional',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $updateData = [
                'name' => $request->name,
                'contact_number' => $request->contact_number,
                'address' => $request->address,
                'wws_id' => $request->wws_id,
                'service' => $request->service,
            ];

            $customer->update($updateData);

            // Refresh the user to get updated data
            $customer->refresh();

            return response()->json([
                'message' => 'Customer updated successfully',
                'customer' => $customer
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update customer: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to update customer',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function activateCustomer(Request $request, $id)
    {
        if (!Auth::user()->isAdmin() && !Auth::user()->isStaff()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $customer = User::where('role', 'client')->findOrFail($id);

        try {
            $customer->update([
                'status' => 'active',
                'deactivated_at' => null,
                'deactivated_by' => null,
            ]);

            return response()->json([
                'message' => 'Customer activated successfully',
                'customer' => $customer
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to activate customer',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    // In your AdminController.php - Fix the markCustomerDelinquent method

    public function markCustomerDelinquent(Request $request, $id)
    {
        if (!Auth::user()->isAdmin() && !Auth::user()->isStaff()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $customer = User::where('role', 'client')->findOrFail($id);

        try {
            $customer->update([
                'status' => 'delinquent',
                // You might want to add additional fields like:
                // 'delinquent_since' => now(),
                // 'delinquent_reason' => $request->reason, // if you want to capture reason
            ]);

            return response()->json([
                'message' => 'Customer marked as delinquent successfully',
                'customer' => $customer
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to mark customer as delinquent: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to mark customer as delinquent',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate a delinquency report for all clients with unpaid/overdue bills.
     */
    public function getDelinquencyReport(Request $request)
    {
        if (!Auth::user()->isAdmin() && !Auth::user()->isStaff()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $asOfInput = $request->input('as_of_date');
            $asOfDate = $asOfInput ? Carbon::parse($asOfInput) : now();
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Invalid as_of_date value',
                'error' => $e->getMessage(),
            ], 422);
        }

        try {
            $openStatuses = ['pending', 'overdue'];

            $delinquentBills = Bill::with('user')
                ->whereIn('status', $openStatuses)
                ->whereDate('due_date', '<=', $asOfDate->toDateString())
                ->get()
                ->groupBy('user_id')
                ->map(function ($bills) {
                    $latestBill = $bills->sortByDesc('due_date')->first();
                    $totalDue = $bills->sum(function (Bill $bill) {
                        if (!is_null($bill->restated_amount)) {
                            return (float) $bill->restated_amount;
                        }

                        if (!is_null($bill->total_payable) && $bill->total_payable > 0) {
                            return (float) $bill->total_payable;
                        }

                        return (float) $bill->amount + (float) $bill->penalty;
                    });

                    $monthsDelinquent = $bills->count();

                    return [
                        'id' => $latestBill->user->id ?? null,
                        'name' => $latestBill->user->name ?? 'Unknown Customer',
                        'meter_reader' => $latestBill->meter_reader,
                        'service' => strtoupper($latestBill->user->service ?? 'RESIDENTIAL'),
                        'months_delinquent' => $monthsDelinquent,
                        'amount_per_month' => $monthsDelinquent > 0
                            ? round($totalDue / $monthsDelinquent, 2)
                            : 0,
                        'total_due' => round($totalDue, 2),
                    ];
                })
                ->values();

            return response()->json([
                'success' => true,
                'as_of_date' => $asOfDate->toDateString(),
                'delinquent_customers' => $delinquentBills,
                'total_due_amount' => round($delinquentBills->sum('total_due'), 2),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to generate delinquency report: ' . $e->getMessage(), [
                'stack' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Failed to generate delinquency report',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    // Also update the deactivateCustomer method to accept reason
    public function deactivateCustomer(Request $request, $id)
    {
        if (!Auth::user()->isAdmin() && !Auth::user()->isStaff()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $customer = User::where('role', 'client')->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'deactivate_reason' => 'required|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $customer->update([
                'status' => 'inactive',
                'deactivate_reason' => $request->deactivate_reason,
                'deactivated_at' => now(),
                'deactivated_by' => Auth::user()->name,
            ]);

            return response()->json([
                'message' => 'Customer deactivated successfully',
                'customer' => $customer
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to deactivate customer: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to deactivate customer',
                'error' => $e->getMessage()
            ], 500);
        }
    }











    // In app/Http/Controllers/AdminController.php

    public function getBills(Request $request)
    {
        if (!Auth::user()->isAdmin() && !Auth::user()->isStaff()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $bills = Bill::with('user')
            ->orderBy('reading_date', 'desc')
            ->get()
            ->map(function ($bill) {
                // Use automatic 10% penalty when the bill is overdue.
                $effectivePenalty = $bill->automatic_penalty;
                $effectiveTotalPayable = $bill->automatic_total_payable;

                return [
                    'id' => $bill->id,
                    'user_id' => $bill->user_id,
                    'wws_id' => $bill->wws_id,
                    'reading_date' => $bill->reading_date,
                    'due_date' => $bill->due_date,
                    'previous_reading' => (float) $bill->previous_reading,
                    'present_reading' => (float) $bill->present_reading,
                    'consumption' => (float) $bill->consumption,
                    'amount' => (float) $bill->amount,
                    'penalty' => $effectivePenalty,
                    'total_payable' => $effectiveTotalPayable,
                    'restated_amount' => $bill->restated_amount ? (float) $bill->restated_amount : null,
                    'restatement_reason' => $bill->restatement_reason,
                    'authorization_code' => $bill->authorization_code,
                    'status' => $bill->status,
                    'meter_reader' => $bill->meter_reader,
                    'online_meter_used' => $bill->online_meter_used,
                    'paid_at' => $bill->paid_at,
                    'qr_number' => $bill->qr_number,
                    'created_at' => $bill->created_at,
                    'updated_at' => $bill->updated_at,
                ];
            });

        return response()->json([
            'bills' => $bills
        ]);
    }

    public function createBill(Request $request)
    {
        if (!Auth::user()->isAdmin() && !Auth::user()->isStaff()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'reading_date' => 'required|date',
            'due_date' => 'required|date|after:reading_date',
            'previous_reading' => 'required|numeric|min:0',
            'present_reading' => 'required|numeric|min:0|gte:previous_reading',
            'amount' => 'required|numeric|min:0',
            'penalty' => 'nullable|numeric|min:0',
            'meter_reader' => 'required|string|max:255',
            'online_meter_used' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $user = User::findOrFail($request->user_id);

            // Calculate consumption
            $consumption = $request->present_reading - $request->previous_reading;

            // Calculate total payable
            $totalPayable = $request->amount + ($request->penalty ?? 0);

            // Generate QR number
            $qrNumber = 'BILL' . date('Ymd') . strtoupper(Str::random(6));

            $bill = Bill::create([
                'user_id' => $request->user_id,
                'wws_id' => $user->wws_id,
                'reading_date' => $request->reading_date,
                'due_date' => $request->due_date,
                'previous_reading' => $request->previous_reading,
                'present_reading' => $request->present_reading,
                'consumption' => $consumption,
                'amount' => $request->amount,
                'penalty' => $request->penalty ?? 0,
                'total_payable' => $totalPayable,
                'meter_reader' => $request->meter_reader,
                'online_meter_used' => $request->online_meter_used ?? false,
                'qr_number' => $qrNumber,
                'status' => 'pending',
            ]);

            return response()->json([
                'message' => 'Bill created successfully',
                'bill' => $bill
            ], 201);
        } catch (\Exception $e) {
            Log::error('Failed to create bill: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to create bill',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateBill(Request $request, $id)
    {
        if (!Auth::user()->isAdmin() && !Auth::user()->isStaff()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $bill = Bill::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'reading_date' => 'required|date',
            'due_date' => 'required|date|after:reading_date',
            'previous_reading' => 'required|numeric|min:0',
            'present_reading' => 'required|numeric|min:0|gte:previous_reading',
            'amount' => 'required|numeric|min:0',
            'penalty' => 'nullable|numeric|min:0',
            'meter_reader' => 'required|string|max:255',
            'online_meter_used' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            // Calculate consumption
            $consumption = $request->present_reading - $request->previous_reading;

            // Calculate total payable
            $totalPayable = $request->amount + ($request->penalty ?? 0);

            $bill->update([
                'reading_date' => $request->reading_date,
                'due_date' => $request->due_date,
                'previous_reading' => $request->previous_reading,
                'present_reading' => $request->present_reading,
                'consumption' => $consumption,
                'amount' => $request->amount,
                'penalty' => $request->penalty ?? 0,
                'total_payable' => $totalPayable,
                'meter_reader' => $request->meter_reader,
                'online_meter_used' => $request->online_meter_used ?? false,
            ]);

            return response()->json([
                'message' => 'Bill updated successfully',
                'bill' => $bill
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update bill: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to update bill',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function restateBillAmount(Request $request, $id)
    {
        if (!Auth::user()->isAdmin() && !Auth::user()->isStaff()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'restated_amount' => 'required|numeric|min:0',
            'restatement_reason' => 'required|string|max:500',
            'authorization_code' => 'required|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Validate authorization code
        if (!AuthorizationCode::validateCode($request->authorization_code)) {
            return response()->json([
                'message' => 'Invalid or expired authorization code',
                'errors' => ['authorization_code' => ['The authorization code is invalid or has expired.']]
            ], 422);
        }

        $bill = Bill::findOrFail($id);

        try {
            $bill->update([
                'restated_amount' => $request->restated_amount,
                'restatement_reason' => $request->restatement_reason,
                'authorization_code' => $request->authorization_code,
                'total_payable' => $request->restated_amount, // Update total payable to restated amount
            ]);

            return response()->json([
                'message' => 'Bill amount restated successfully',
                'bill' => $bill
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to restate bill amount: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to restate bill amount',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function markBillAsPaid(Request $request, $id)
    {
        if (!Auth::user()->isAdmin() && !Auth::user()->isStaff()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $bill = Bill::findOrFail($id);

        try {
            $bill->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);

            return response()->json([
                'message' => 'Bill marked as paid successfully',
                'bill' => $bill
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to mark bill as paid: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to mark bill as paid',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function deleteBill($id)
    {
        if (!Auth::user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $bill = Bill::findOrFail($id);

        try {
            $bill->delete();

            return response()->json([
                'message' => 'Bill deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to delete bill: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to delete bill',
                'error' => $e->getMessage()
            ], 500);
        }
    }













    // In app/Http/Controllers/AdminController.php

    public function getMeterReadings(Request $request)
    {
        if (!Auth::user()->isAdmin() && !Auth::user()->isStaff()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $readings = Bill::with('user')
            ->orderBy('reading_date', 'desc')
            ->get()
            ->map(function ($reading) {
                return [
                    'id' => $reading->id,
                    'user_id' => $reading->user_id,
                    'wws_id' => $reading->wws_id,
                    'reading_date' => $reading->reading_date,
                    'due_date' => $reading->due_date,
                    'previous_reading' => (float) $reading->previous_reading,
                    'present_reading' => (float) $reading->present_reading,
                    'consumption' => (float) $reading->consumption,
                    'amount' => (float) $reading->amount,
                    'penalty' => (float) $reading->penalty,
                    'total_payable' => (float) $reading->total_payable,
                    'restated_amount' => $reading->restated_amount ? (float) $reading->restated_amount : null,
                    'restatement_reason' => $reading->restatement_reason,
                    'authorization_code' => $reading->authorization_code,
                    'status' => $reading->status,
                    'meter_reader' => $reading->meter_reader,
                    'online_meter_used' => $reading->online_meter_used,
                    'paid_at' => $reading->paid_at,
                    'qr_number' => $reading->qr_number,
                    'created_at' => $reading->created_at,
                    'updated_at' => $reading->updated_at,
                ];
            });

        return response()->json([
            'readings' => $readings
        ]);
    }

    public function createMeterReading(Request $request)
    {
        if (!Auth::user()->isAdmin() && !Auth::user()->isStaff()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'reading_date' => 'required|date',
            'due_date' => 'required|date|after:reading_date',
            'previous_reading' => 'required|numeric|min:0',
            'present_reading' => 'required|numeric|min:0|gte:previous_reading',
            'meter_reader' => 'required|string|max:255',
            'online_meter_used' => 'boolean',
            'amount' => 'nullable|numeric|min:0',
            'penalty' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $user = User::findOrFail($request->user_id);

            // Calculate consumption
            $consumption = $request->present_reading - $request->previous_reading;

            // Calculate amount based on service type if not provided
            $amount = $request->amount;
            if (!$amount && $consumption > 0) {
                $rates = [
                    'residential' => 50.00,
                    'commercial' => 75.00,
                    'institutional' => 60.00
                ];
                $rate = $rates[$user->service] ?? $rates['residential'];
                $amount = $consumption * $rate;
            }

            // Calculate total payable
            $totalPayable = $amount + ($request->penalty ?? 0);

            // Generate QR number
            $qrNumber = 'READ' . date('YmdHis') . strtoupper(Str::random(4));

            $reading = Bill::create([
                'user_id' => $request->user_id,
                'wws_id' => $user->wws_id,
                'reading_date' => $request->reading_date,
                'due_date' => $request->due_date,
                'previous_reading' => $request->previous_reading,
                'present_reading' => $request->present_reading,
                'consumption' => $consumption,
                'amount' => $amount ?? 0,
                'penalty' => $request->penalty ?? 0,
                'total_payable' => $totalPayable,
                'meter_reader' => $request->meter_reader,
                'online_meter_used' => $request->online_meter_used ?? false,
                'qr_number' => $qrNumber,
                'status' => 'pending',
            ]);

            return response()->json([
                'message' => 'Meter reading created successfully',
                'reading' => $reading
            ], 201);
        } catch (\Exception $e) {
            Log::error('Failed to create meter reading: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to create meter reading',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateMeterReading(Request $request, $id)
    {
        if (!Auth::user()->isAdmin() && !Auth::user()->isStaff()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $reading = Bill::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'reading_date' => 'required|date',
            'due_date' => 'required|date|after:reading_date',
            'previous_reading' => 'required|numeric|min:0',
            'present_reading' => 'required|numeric|min:0|gte:previous_reading',
            'meter_reader' => 'required|string|max:255',
            'online_meter_used' => 'boolean',
            'amount' => 'nullable|numeric|min:0',
            'penalty' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            // Calculate consumption
            $consumption = $request->present_reading - $request->previous_reading;

            // Calculate total payable
            $totalPayable = ($request->amount ?? $reading->amount) + ($request->penalty ?? $reading->penalty);

            $reading->update([
                'reading_date' => $request->reading_date,
                'due_date' => $request->due_date,
                'previous_reading' => $request->previous_reading,
                'present_reading' => $request->present_reading,
                'consumption' => $consumption,
                'amount' => $request->amount ?? $reading->amount,
                'penalty' => $request->penalty ?? $reading->penalty,
                'total_payable' => $totalPayable,
                'meter_reader' => $request->meter_reader,
                'online_meter_used' => $request->online_meter_used ?? false,
            ]);

            return response()->json([
                'message' => 'Meter reading updated successfully',
                'reading' => $reading
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update meter reading: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to update meter reading',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function restateReadingAmount(Request $request, $id)
    {
        if (!Auth::user()->isAdmin() && !Auth::user()->isStaff()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'restated_amount' => 'required|numeric|min:0',
            'restatement_reason' => 'required|string|max:500',
            'authorization_code' => 'required|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Validate authorization code
        if (!AuthorizationCode::validateCode($request->authorization_code)) {
            return response()->json([
                'message' => 'Invalid or expired authorization code',
                'errors' => ['authorization_code' => ['The authorization code is invalid or has expired.']]
            ], 422);
        }

        $reading = Bill::findOrFail($id);

        try {
            $reading->update([
                'restated_amount' => $request->restated_amount,
                'restatement_reason' => $request->restatement_reason,
                'authorization_code' => $request->authorization_code,
                'total_payable' => $request->restated_amount,
            ]);

            return response()->json([
                'message' => 'Reading amount restated successfully',
                'reading' => $reading
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to restate reading amount: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to restate reading amount',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function deleteMeterReading($id)
    {
        if (!Auth::user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $reading = Bill::findOrFail($id);

        try {
            $reading->delete();

            return response()->json([
                'message' => 'Meter reading deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to delete meter reading: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to delete meter reading',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Authorization Code Management Methods
    public function getAuthorizationCodes(Request $request)
    {
        if (!Auth::user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $codes = AuthorizationCode::with('creator')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'codes' => $codes
        ]);
    }

    public function createAuthorizationCode(Request $request)
    {
        if (!Auth::user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'code' => 'required|string|max:50|unique:authorization_codes,code',
            'description' => 'nullable|string|max:255',
            'expires_at' => 'nullable|date|after:now',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $code = AuthorizationCode::create([
                'code' => $request->code,
                'description' => $request->description,
                'is_active' => $request->has('is_active') ? $request->is_active : true,
                'created_by' => Auth::id(),
                'expires_at' => $request->expires_at,
            ]);

            return response()->json([
                'message' => 'Authorization code created successfully',
                'code' => $code->load('creator')
            ], 201);
        } catch (\Exception $e) {
            Log::error('Failed to create authorization code: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to create authorization code',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateAuthorizationCode(Request $request, $id)
    {
        if (!Auth::user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $code = AuthorizationCode::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'code' => 'required|string|max:50|unique:authorization_codes,code,' . $id,
            'description' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'expires_at' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $code->update([
                'code' => $request->code,
                'description' => $request->description,
                'is_active' => $request->has('is_active') ? $request->is_active : $code->is_active,
                'expires_at' => $request->expires_at,
            ]);

            return response()->json([
                'message' => 'Authorization code updated successfully',
                'code' => $code->load('creator')
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update authorization code: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to update authorization code',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function deleteAuthorizationCode($id)
    {
        if (!Auth::user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $code = AuthorizationCode::findOrFail($id);

        try {
            $code->delete();

            return response()->json([
                'message' => 'Authorization code deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to delete authorization code: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to delete authorization code',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getPayments(Request $request)
    {
        if (!Auth::user()->isAdmin() && !Auth::user()->isStaff()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $payments = Payment::with(['user', 'bill'])
                ->orderBy('payment_date', 'desc')
                ->get()
                ->map(function ($payment) {
                    return [
                        'id' => $payment->id,
                        'user_id' => $payment->user_id,
                        'bill_id' => $payment->bill_id,
                        'wws_id' => $payment->wws_id,
                        'amount_paid' => (float) $payment->amount_paid,
                        'payment_date' => $payment->payment_date ? $payment->payment_date->format('Y-m-d\TH:i:s\Z') : null,
                        'qr_number' => $payment->qr_number,
                        'qr_date' => $payment->qr_date ? $payment->qr_date->format('Y-m-d') : null,
                        'balance' => (float) $payment->balance,
                        'payment_method' => $payment->payment_method,
                        'electronic_qr_number' => $payment->electronic_qr_number,
                        'electronic_amount' => $payment->electronic_amount ? (float) $payment->electronic_amount : null,
                        'payment_gateway' => $payment->payment_gateway,
                        'gateway_reference' => $payment->gateway_reference,
                        'gateway_transaction_id' => $payment->gateway_transaction_id,
                        'payment_status' => $payment->payment_status,
                        'status' => $payment->status,
                        'collector_name' => $payment->collector_name,
                        'processed_at' => $payment->processed_at ? $payment->processed_at->format('Y-m-d\TH:i:s\Z') : null,
                        'failure_reason' => $payment->failure_reason,
                        'created_at' => $payment->created_at ? $payment->created_at->format('Y-m-d\TH:i:s\Z') : null,
                        'updated_at' => $payment->updated_at ? $payment->updated_at->format('Y-m-d\TH:i:s\Z') : null,
                    ];
                });

            return response()->json([
                'success' => true,
                'payments' => $payments
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch payments: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to fetch payments',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function processPayment(Request $request, $id)
    {
        if (!Auth::user()->isAdmin() && !Auth::user()->isStaff()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $payment = Payment::findOrFail($id);

        try {
            $validator = Validator::make($request->all(), [
                'payment_status' => 'required|in:pending,processing,completed,failed,cancelled',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            DB::beginTransaction();
            
            $payment->update([
                'payment_status' => $request->payment_status,
                'status' => $request->payment_status === 'completed' ? 'completed' : $payment->status,
                'processed_at' => $request->payment_status === 'completed' ? now() : $payment->processed_at,
            ]);

            // If payment is completed, also update the associated bill
            if ($request->payment_status === 'completed') {
                $bill = $payment->bill;
                if ($bill && $bill->status !== 'paid') {
                    $bill->update([
                        'status' => 'paid',
                        'paid_at' => now(),
                    ]);
                }
            }

            DB::commit();
            
            // Refresh payment to get latest data
            $payment->refresh();

            return response()->json([
                'message' => 'Payment processed successfully',
                'payment' => $payment
            ]);
        } catch (\Exception $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            Log::error('Failed to process payment: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to process payment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getCollectionReports(Request $request)
    {
        if (!Auth::user()->isAdmin() && !Auth::user()->isStaff()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            $reportType = $request->input('report_type', 'monthly');

            $paymentsQuery = Payment::where(function($query) {
                    $query->where('status', 'completed')
                          ->orWhere('payment_status', 'completed');
                })
                ->with(['user', 'bill']);

            if ($startDate) {
                $paymentsQuery->whereDate('payment_date', '>=', $startDate);
            }

            if ($endDate) {
                $paymentsQuery->whereDate('payment_date', '<=', $endDate);
            }

            $payments = $paymentsQuery->orderBy('payment_date', 'desc')->get();

            $reports = [];

            if ($reportType === 'monthly') {
                // Group by month
                $grouped = $payments->groupBy(function ($payment) {
                    return \Illuminate\Support\Carbon::parse($payment->payment_date)->format('Y-m');
                });

                foreach ($grouped as $month => $monthPayments) {
                    $onlinePayments = $monthPayments->where('payment_method', 'online');
                    $counterPayments = $monthPayments->where('payment_method', 'over_the_counter');
                    $uniqueCustomers = $monthPayments->pluck('user_id')->unique()->count();

                    $reports[] = [
                        'period' => \Illuminate\Support\Carbon::parse($month . '-01')->format('F Y'),
                        'period_key' => $month,
                        'total_collected' => (float) $monthPayments->sum('amount_paid'),
                        'total_customers' => $uniqueCustomers,
                        'total_transactions' => $monthPayments->count(),
                        'online_collections' => (float) $onlinePayments->sum('amount_paid'),
                        'counter_collections' => (float) $counterPayments->sum('amount_paid'),
                        'online_count' => $onlinePayments->count(),
                        'counter_count' => $counterPayments->count(),
                    ];
                }

                // Sort by period (newest first)
                usort($reports, function ($a, $b) {
                    return strcmp($b['period_key'], $a['period_key']);
                });
            } else if ($reportType === 'daily') {
                // Group by day
                $grouped = $payments->groupBy(function ($payment) {
                    return \Illuminate\Support\Carbon::parse($payment->payment_date)->format('Y-m-d');
                });

                foreach ($grouped as $day => $dayPayments) {
                    $onlinePayments = $dayPayments->where('payment_method', 'online');
                    $counterPayments = $dayPayments->where('payment_method', 'over_the_counter');
                    $uniqueCustomers = $dayPayments->pluck('user_id')->unique()->count();

                    $reports[] = [
                        'period' => \Illuminate\Support\Carbon::parse($day)->format('M d, Y'),
                        'period_key' => $day,
                        'total_collected' => (float) $dayPayments->sum('amount_paid'),
                        'total_customers' => $uniqueCustomers,
                        'total_transactions' => $dayPayments->count(),
                        'online_collections' => (float) $onlinePayments->sum('amount_paid'),
                        'counter_collections' => (float) $counterPayments->sum('amount_paid'),
                        'online_count' => $onlinePayments->count(),
                        'counter_count' => $counterPayments->count(),
                    ];
                }

                // Sort by period (newest first)
                usort($reports, function ($a, $b) {
                    return strcmp($b['period_key'], $a['period_key']);
                });
            } else {
                // Overall summary
                $onlinePayments = $payments->where('payment_method', 'online');
                $counterPayments = $payments->where('payment_method', 'over_the_counter');
                $uniqueCustomers = $payments->pluck('user_id')->unique()->count();

                $periodStartLabel = $startDate
                    ? \Illuminate\Support\Carbon::parse($startDate)->format('M d, Y')
                    : 'Earliest Record';
                $periodEndLabel = $endDate
                    ? \Illuminate\Support\Carbon::parse($endDate)->format('M d, Y')
                    : 'Latest Record';

                $reports[] = [
                    'period' => $periodStartLabel . ' - ' . $periodEndLabel,
                    'period_key' => ($startDate ?? 'all') . '_' . ($endDate ?? 'latest'),
                    'total_collected' => (float) $payments->sum('amount_paid'),
                    'total_customers' => $uniqueCustomers,
                    'total_transactions' => $payments->count(),
                    'online_collections' => (float) $onlinePayments->sum('amount_paid'),
                    'counter_collections' => (float) $counterPayments->sum('amount_paid'),
                    'online_count' => $onlinePayments->count(),
                    'counter_count' => $counterPayments->count(),
                ];
            }

            return response()->json([
                'success' => true,
                'reports' => $reports,
                'summary' => [
                    'total_collected' => (float) $payments->sum('amount_paid'),
                    'total_customers' => $payments->pluck('user_id')->unique()->count(),
                    'total_transactions' => $payments->count(),
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'report_type' => $reportType,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch collection reports: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to fetch collection reports',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function exportCollectionReports(Request $request)
    {
        if (!Auth::user()->isAdmin() && !Auth::user()->isStaff()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            $reportType = $request->input('report_type', 'monthly');
            $format = $request->input('format', 'csv');

            $paymentsQuery = Payment::where(function($query) {
                    $query->where('status', 'completed')
                          ->orWhere('payment_status', 'completed');
                })
                ->with(['user', 'bill']);

            if ($startDate) {
                $paymentsQuery->whereDate('payment_date', '>=', $startDate);
            }

            if ($endDate) {
                $paymentsQuery->whereDate('payment_date', '<=', $endDate);
            }

            $payments = $paymentsQuery->orderBy('payment_date', 'desc')->get();

            $reports = [];
            
            if ($reportType === 'monthly') {
                $grouped = $payments->groupBy(function ($payment) {
                    return \Illuminate\Support\Carbon::parse($payment->payment_date)->format('Y-m');
                });

                foreach ($grouped as $month => $monthPayments) {
                    $onlinePayments = $monthPayments->where('payment_method', 'online');
                    $counterPayments = $monthPayments->where('payment_method', 'over_the_counter');
                    $uniqueCustomers = $monthPayments->pluck('user_id')->unique()->count();

                    $reports[] = [
                        'period' => \Illuminate\Support\Carbon::parse($month . '-01')->format('F Y'),
                        'total_collected' => (float) $monthPayments->sum('amount_paid'),
                        'total_customers' => $uniqueCustomers,
                        'total_transactions' => $monthPayments->count(),
                        'online_collections' => (float) $onlinePayments->sum('amount_paid'),
                        'counter_collections' => (float) $counterPayments->sum('amount_paid'),
                    ];
                }
            } else {
                $onlinePayments = $payments->where('payment_method', 'online');
                $counterPayments = $payments->where('payment_method', 'over_the_counter');
                $uniqueCustomers = $payments->pluck('user_id')->unique()->count();

                $periodStartLabel = $startDate
                    ? \Illuminate\Support\Carbon::parse($startDate)->format('M d, Y')
                    : 'Earliest Record';
                $periodEndLabel = $endDate
                    ? \Illuminate\Support\Carbon::parse($endDate)->format('M d, Y')
                    : 'Latest Record';

                $reports[] = [
                    'period' => $periodStartLabel . ' - ' . $periodEndLabel,
                    'total_collected' => (float) $payments->sum('amount_paid'),
                    'total_customers' => $uniqueCustomers,
                    'total_transactions' => $payments->count(),
                    'online_collections' => (float) $onlinePayments->sum('amount_paid'),
                    'counter_collections' => (float) $counterPayments->sum('amount_paid'),
                ];
            }

            if (in_array($format, ['excel', 'csv'])) {
                // Generate CSV
                $csv = "Period,Total Collected,Total Customers,Total Transactions,Online Collections,Counter Collections\n";
                foreach ($reports as $report) {
                    $csv .= sprintf(
                        "%s,%.2f,%d,%d,%.2f,%.2f\n",
                        $report['period'],
                        $report['total_collected'],
                        $report['total_customers'],
                        $report['total_transactions'],
                        $report['online_collections'],
                        $report['counter_collections']
                    );
                }

                $exportStart = $startDate ?? 'all';
                $exportEnd = $endDate ?? 'latest';

                return response($csv, 200)
                    ->header('Content-Type', 'text/csv')
                    ->header('Content-Disposition', 'attachment; filename="collection-report-' . $exportStart . '-to-' . $exportEnd . '.csv"');
            } else {
                return response()->json([
                    'message' => 'Unsupported export format.',
                    'supported_formats' => ['excel', 'csv'],
                ], 400);
            }
        } catch (\Exception $e) {
            Log::error('Failed to export collection reports: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to export collection reports',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
