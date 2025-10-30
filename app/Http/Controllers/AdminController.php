<?php
// app/Http/Controllers/AdminController.php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

        // Only allow approval of pending users
        if ($user->status !== 'pending') {
            return response()->json([
                'message' => 'User is not pending approval'
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
}