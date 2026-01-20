<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::prefix('v1')->group(function () {
    // Authentication
    Route::post('/register/coach', [AuthController::class, 'registerCoach']);
    Route::post('/login', [AuthController::class, 'login']);
    
    // Health check
    Route::get('/health', function () {
        return response()->json([
            'success' => true,
            'message' => 'CloudEwork API is running',
            'version' => '1.0.0',
            'timestamp' => now()->toIso8601String(),
        ]);
    });
});

// Protected routes
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    // Authentication
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    
    // Athletes routes (coming next)
    // Route::apiResource('athletes', AthleteController::class);
    
    // Workouts routes (coming next)
    // Route::apiResource('workouts', WorkoutController::class);
    
    // Assignments routes (coming next)
    // Route::apiResource('assignments', AssignmentController::class);
    
    // Results routes (coming next)
    // Route::apiResource('results', ResultController::class);
    
    // Analytics routes (coming next)
    // Route::get('/analytics/dashboard', [AnalyticsController::class, 'dashboard']);
});

// Fallback route
Route::fallback(function () {
    return response()->json([
        'success' => false,
        'message' => 'Endpoint not found',
    ], 404);
});
