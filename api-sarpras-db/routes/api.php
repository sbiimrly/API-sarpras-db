<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\User\UserController;
use App\Http\Controllers\Admin\LaporanController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ArsipController;

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

// API Routes untuk Laporan
Route::prefix('admin')->group(function () {
    // Laporan routes
    Route::get('/laporan', [LaporanController::class, 'index']);
    Route::post('/laporan/archive', [LaporanController::class, 'archive']);
    Route::post('/laporan/destroy', [LaporanController::class, 'destroy']);
    Route::get('/laporan/{id}', [LaporanController::class, 'show']);
    Route::put('/laporan/{id}/status', [LaporanController::class, 'updateStatus']);

    // Arsip routes
    Route::get('/arsip', [ArsipController::class, 'index']);
    Route::post('/arsip/restore', [ArsipController::class, 'restore']);
    Route::post('/arsip/destroy', [ArsipController::class, 'destroy']);
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
});
