<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\User\UserController;
use App\Http\Controllers\Admin\LaporanController;
use App\Http\Controllers\Admin\DashboardController;

// Test route
Route::get('/test', function () {
    return response()->json([
        'message' => 'BE API is working!',
        'timestamp' => now()
    ]);
});

// Public routes
Route::post('/login', [AuthController::class, 'login']);

// Laporan routes
Route::post('/laporan', [UserController::class, 'store']);
Route::get('/laporan', [LaporanController::class, 'index']);

// Dashboard routes
Route::get('/dashboard', [DashboardController::class, 'getDashboardData']);
Route::get('/dashboard/filter', [DashboardController::class, 'filter']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
});
