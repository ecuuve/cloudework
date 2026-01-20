<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AthleteController;
use App\Http\Controllers\Api\WorkoutController;
use App\Http\Controllers\Api\AssignmentController;
use App\Http\Controllers\Api\ResultController;
use App\Http\Controllers\Api\AnalyticsController;

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
    
    // Athletes
    Route::apiResource('athletes', AthleteController::class);
    
    // Workouts
    Route::apiResource('workouts', WorkoutController::class);
    Route::get('/benchmarks', [WorkoutController::class, 'benchmarks']);
    
    // Assignments
    Route::apiResource('assignments', AssignmentController::class);
    Route::post('/assignments/bulk', [AssignmentController::class, 'bulkAssign']);
    Route::get('/calendar', [AssignmentController::class, 'calendar']);
    
    // Results
    Route::apiResource('results', ResultController::class)->only(['index', 'store', 'update']);
    Route::get('/results/workout/{workoutId}/history', [ResultController::class, 'workoutHistory']);
    Route::get('/personal-records', [ResultController::class, 'personalRecords']);
    
    // Analytics
    Route::get('/analytics/dashboard', [AnalyticsController::class, 'dashboard']);
    Route::get('/analytics/athlete/{athleteId}/progress', [AnalyticsController::class, 'athleteProgress']);
    Route::get('/analytics/workout/{workoutId}/leaderboard', [AnalyticsController::class, 'workoutLeaderboard']);
});

// Fallback route
Route::fallback(function () {
    return response()->json([
        'success' => false,
        'message' => 'Endpoint not found',
    ], 404);
});
