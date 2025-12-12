<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\User\UserController;
use App\Http\Controllers\Admin\LaporanController;
use App\Http\Controllers\Admin\DashboardController;

// Allow preflight OPTIONS requests
Route::options('/{any}', function () {
    return response()->json([], 200);
})->where('any', '.*');

Route::get('/test-api-check', function () {
    return response()->json(['message' => 'API Loaded']);
});

// Public routes
Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:admin-login');

Route::post('/laporan', [UserController::class, 'store']);

Route::get('/laporan', [LaporanController::class, 'index']);

// PERBAIKAN: Hapus middleware auth:sanctum untuk sementara testing
Route::get('/dashboard', [DashboardController::class, 'getDashboardData']);
Route::get('/dashboard/filter', [DashboardController::class, 'filter']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
});
