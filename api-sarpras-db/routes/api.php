<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

// Allow preflight OPTIONS requests
Route::options('/{any}', function () {
    return response()->json([], 200);
})->where('any', '.*');

Route::get('/test-api-check', function () {
    return 'API Loaded';
});

// Authentication Routes
Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:admin-login');

Route::post('/logout', [AuthController::class, 'logout'])
->middleware('auth:sanctum');

Route::get('/user', [AuthController::class, 'user'])
->middleware('auth:sanctum');


