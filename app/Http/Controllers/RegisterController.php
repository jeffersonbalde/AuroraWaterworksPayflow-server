<?php
// app/Http/Controllers/RegisterController.php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class RegisterController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'wwsId' => 'required|string|max:255|unique:users,wws_id',
            'fullName' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'contactNumber' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::create([
            'wws_id' => $request->wwsId,
            'name' => $request->fullName,
            'email' => $request->email,
            'contact_number' => $request->contactNumber,
            'address' => $request->address,
            'role' => 'client', 
            'status' => 'pending', 
            'password' => Hash::make($request->password),
        ]);

        // Generate token for immediate login after registration
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'message' => 'Registration successful! Your account is pending approval. You will receive an email once activated.',
            'user' => $user,
            'token' => $token,
            'redirect_to' => '/dashboard' // ALWAYS redirect to /dashboard
        ], 201);
    }
}