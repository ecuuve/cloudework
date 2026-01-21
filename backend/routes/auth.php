<?php

use Illuminate\Support\Facades\Route;

// Named route for login (required by Sanctum)
Route::post('/login', function () {
    return response()->json(['message' => 'Please use /api/v1/login'], 404);
})->name('login');
