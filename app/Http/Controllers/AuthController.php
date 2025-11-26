<?php
// app/Http/Controllers/AuthController.php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'The provided credentials are incorrect.'
            ], 401);
        }

        // Check if account is deactivated (applies to all users)
        if ($user->status === 'inactive') {
            $deactivationInfo = [
                'deactivated' => true,
                'deactivate_reason' => $user->deactivate_reason,
                'deactivated_at' => $user->deactivated_at,
                'deactivated_by' => $user->deactivated_by,
            ];
            
            return response()->json([
                'success' => false,
                'error' => 'Account Deactivated',
                'message' => 'Your account has been deactivated.',
                'deactivation_info' => $deactivationInfo
            ], 403);
        }

        // MODIFIED: Allow ALL client users to login regardless of status
        if ($user->role === 'client') {
            $token = $user->createToken('auth-token')->plainTextToken;

            $response = [
                'success' => true,
                'user' => $user,
                'token' => $token,
                'redirect_to' => '/dashboard'
            ];

            // Add status info for frontend
            if ($user->status === 'rejected') {
                $response['status_info'] = [
                    'rejected' => true,
                    'rejection_reason' => $user->rejection_reason,
                    'rejected_at' => $user->rejected_at,
                    'rejected_by' => $user->rejected_by,
                ];
            } elseif ($user->status === 'pending') {
                $response['status_info'] = [
                    'pending' => true,
                    'message' => 'Your account is pending approval.'
                ];
            }

            return response()->json($response);
        }

        // For staff/admin users, maintain existing restrictions
        if ($user->status === 'pending') {
            return response()->json([
                'success' => false,
                'error' => 'Account Pending Approval',
                'message' => 'Your account is pending approval. Please contact the administrator.'
            ], 403);
        }

        if ($user->status === 'rejected') {
            $rejectionInfo = [
                'rejected' => true,
                'rejection_reason' => $user->rejection_reason,
                'rejected_at' => $user->rejected_at,
                'rejected_by' => $user->rejected_by,
            ];
            
            return response()->json([
                'success' => false,
                'error' => 'Account Rejected',
                'message' => 'Your account registration has been rejected.',
                'rejection_info' => $rejectionInfo
            ], 403);
        }

        // Normal login for active staff/admin users
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'user' => $user,
            'token' => $token,
            'redirect_to' => '/dashboard'
        ]);
    }

    // ... rest of your methods remain the same
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Successfully logged out'
        ]);
    }

    public function user(Request $request)
    {
        return response()->json($request->user());
    }
}