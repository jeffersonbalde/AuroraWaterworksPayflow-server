<?php

use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// In routes/web.php
Route::prefix('payment')->group(function () {
    Route::get('/success/{gateway?}', [PaymentController::class, 'success']);
    Route::get('/cancel/{gateway?}', [PaymentController::class, 'cancel']);
    Route::get('/failed', [PaymentController::class, 'failed']);
    Route::get('/demo-success/{paymentId}', [PaymentController::class, 'demoSuccess']);
});