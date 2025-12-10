<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::options('/{any}', function () {
    return response()->json([], 200);
})->where('any', '.*');
