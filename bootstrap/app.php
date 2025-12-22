<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Http\Middleware\HandleCors;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            RateLimiter::for('admin-login', function (Request $request) {
                $key = 'login:' . $request->ip() . ':' . strtolower($request->email);
                return Limit::perMinute(5)->by($key)->response(function () {
                    return response()->json([
                        'message' => 'Terlalu banyak percobaan login. Coba lagi dalam 10 menit.'
                    ], 429);
                });
            });

            // Rate limiter untuk laporan
            RateLimiter::for('laporan-submit', function (Request $request) {
                $key = 'laporan:' . $request->ip() . ':' . $request->email;
                return Limit::perMinutes(10, 1)->by($key)->response(function () {
                    return response()->json([
                        'message' => 'Anda baru saja mengirim laporan. Tunggu 10 menit untuk mengirim laporan berikutnya.',
                        'wait_time' => 600 // 10 menit dalam detik
                    ], 429);
                });
            });
        }
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
