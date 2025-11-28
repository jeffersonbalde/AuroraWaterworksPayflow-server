<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RegisterController;
use Illuminate\Http\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::post('/register', [RegisterController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    Route::put('/profile', [ProfileController::class, 'update']);
    Route::post('/profile/avatar', [ProfileController::class, 'updateAvatar']);

    Route::put('/profile/password', [ProfileController::class, 'changePassword']);

    // Admin/Staff routes for user management
    Route::prefix('admin')->group(function () {
        Route::get('/users', [AdminController::class, 'getUsers']);
        Route::post('/users/{id}/approve', [AdminController::class, 'approveUser']);
        Route::post('/users/{id}/reject', [AdminController::class, 'rejectUser']);


        // New staff management routes
        Route::post('/users', [AdminController::class, 'createStaff']);
        Route::put('/users/{id}', [AdminController::class, 'updateStaff']);
        Route::delete('/users/{id}', [AdminController::class, 'deleteStaff']);


        // Staff deactivation routes
        Route::patch('/users/{id}/deactivate', [AdminController::class, 'deactivateStaff']);
        Route::patch('/users/{id}/activate', [AdminController::class, 'activateStaff']);


        Route::get('/customers', [AdminController::class, 'getCustomers']);
        Route::put('/customers/{id}', [AdminController::class, 'updateCustomer']);
        Route::patch('/customers/{id}/deactivate', [AdminController::class, 'deactivateCustomer']);
        Route::patch('/customers/{id}/activate', [AdminController::class, 'activateCustomer']);
        Route::patch('/customers/{id}/mark-delinquent', [AdminController::class, 'markCustomerDelinquent']);



        // Meter Reading Routes
        Route::get('/meter-readings', [AdminController::class, 'getMeterReadings']);
        Route::post('/meter-readings', [AdminController::class, 'createMeterReading']);
        Route::put('/meter-readings/{id}', [AdminController::class, 'updateMeterReading']);
        Route::patch('/meter-readings/{id}/restate', [AdminController::class, 'restateReadingAmount']);
        Route::delete('/meter-readings/{id}', [AdminController::class, 'deleteMeterReading']);


        // Payment Tracking Routes
        Route::get('/payments', [AdminController::class, 'getPayments']);
        Route::patch('/payments/{id}/process', [AdminController::class, 'processPayment']);

        // Collection Reports Routes
        Route::get('/collection-reports', [AdminController::class, 'getCollectionReports']);
        Route::get('/collection-reports/export', [AdminController::class, 'exportCollectionReports']);
        Route::get('/delinquency-report', [AdminController::class, 'getDelinquencyReport']);
        Route::post('/delinquency-notice', [AdminController::class, 'generateDelinquencyNotice']);


        // Billing Management Routes
        Route::get('/bills', [AdminController::class, 'getBills']);
        Route::post('/bills', [AdminController::class, 'createBill']);
        Route::put('/bills/{id}', [AdminController::class, 'updateBill']);
        Route::patch('/bills/{id}/restate', [AdminController::class, 'restateBillAmount']);
        Route::patch('/bills/{id}/mark-paid', [AdminController::class, 'markBillAsPaid']);
        Route::delete('/bills/{id}', [AdminController::class, 'deleteBill']);

        // Authorization Code Management Routes (Admin Only)
        Route::get('/authorization-codes', [AdminController::class, 'getAuthorizationCodes']);
        Route::post('/authorization-codes', [AdminController::class, 'createAuthorizationCode']);
        Route::put('/authorization-codes/{id}', [AdminController::class, 'updateAuthorizationCode']);
        Route::delete('/authorization-codes/{id}', [AdminController::class, 'deleteAuthorizationCode']);
    });

    // Client routes
    Route::prefix('client')->middleware('auth:sanctum')->group(function () {
        Route::get('/bills', [ClientController::class, 'getBills']);
        Route::get('/payments', [ClientController::class, 'getPayments']);
        Route::get('/usage', [ClientController::class, 'getUsage']);
        Route::get('/pending-bills', [ClientController::class, 'getPendingBills']);
        Route::post('/make-payment', [ClientController::class, 'makePayment']);
    });

    // In routes/api.php
    Route::prefix('payment')->group(function () {
        Route::get('/status/{paymentId}', [PaymentController::class, 'checkStatus']);
    });
    
    // Webhook route (no auth required - PayMongo calls this directly)
    Route::post('/payment/webhook/{gateway}', [PaymentController::class, 'webhook']);

    Route::get('/dashboard/client-data', [DashboardController::class, 'getClientDashboardData']);
});


// FIXED API Route - Remove mimeType() call
Route::get('/avatar/{filename}', function ($filename) {
    // Clean the filename - remove any "avatars/" prefix if present
    $cleanFilename = str_replace('avatars/', '', $filename);
    $cleanFilename = str_replace('avatar_', '', $cleanFilename);

    $path = 'avatars/' . $cleanFilename;

    if (!Storage::disk('public')->exists($path)) {
        Log::error("Avatar not found: " . $path);
        abort(404);
    }

    $file = Storage::disk('public')->get($path);

    // Get mime type using a different approach
    $mimeType = mime_content_type(storage_path('app/public/' . $path));

    return response($file, 200)
        ->header('Content-Type', $mimeType)
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Cross-Origin-Resource-Policy', 'cross-origin');
});
