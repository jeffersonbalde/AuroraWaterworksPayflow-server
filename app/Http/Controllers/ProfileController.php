<?php
// app/Http/Controllers/ProfileController.php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    public function update(Request $request)
    {
        $user = Auth::user();

        // Role-based validation rules
        $rules = [];
        $updateData = [];

        if ($user->isAdmin()) {
            // Admin can only change password
            $rules = [
                'current_password' => 'nullable|required_with:new_password',
                'new_password' => 'nullable|min:6|confirmed',
            ];
        } else if ($user->isStaff()) {
            // Staff can update personal info and position
            $rules = [
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email,' . $user->id,
                'contact_number' => 'nullable|string|max:20',
                'position' => 'nullable|string|max:255',
                'current_password' => 'nullable|required_with:new_password',
                'new_password' => 'nullable|min:6|confirmed',
            ];
            
            $updateData = [
                'name' => $request->name,
                'email' => $request->email,
                'contact_number' => $request->contact_number,
                'position' => $request->position,
            ];
        } else {
            // Client can update personal info
            $rules = [
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email,' . $user->id,
                'contact_number' => 'nullable|string|max:20',
                'address' => 'nullable|string|max:500',
                'current_password' => 'nullable|required_with:new_password',
                'new_password' => 'nullable|min:6|confirmed',
            ];
            
            $updateData = [
                'name' => $request->name,
                'email' => $request->email,
                'contact_number' => $request->contact_number,
                'address' => $request->address,
            ];
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        // Handle password change for all roles
        if ($request->filled('current_password') && $request->filled('new_password')) {
            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'message' => 'Current password is incorrect'
                ], 422);
            }
            $updateData['password'] = Hash::make($request->new_password);
        }

        // Update the user
        $user->update($updateData);

        // Get fresh user data
        $updatedUser = User::find($user->id);

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $updatedUser
        ]);
    }

    public function updateAvatar(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'avatar' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();
        
        $user->update([
            'avatar' => $request->avatar
        ]);

        $updatedUser = User::find($user->id);

        return response()->json([
            'message' => 'Avatar updated successfully',
            'user' => $updatedUser
        ]);
    }

    public function show()
    {
        $user = Auth::user();
        return response()->json([
            'user' => $user
        ]);
    }

    public function changePassword(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'new_password' => 'required|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'Current password is incorrect'
            ], 422);
        }

        $user->update([
            'password' => Hash::make($request->new_password)
        ]);

        return response()->json([
            'message' => 'Password changed successfully'
        ]);
    }
}