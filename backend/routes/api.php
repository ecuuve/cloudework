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
| API Routes - /api/v1/*
|--------------------------------------------------------------------------
| Laravel 11 automáticamente agrega el prefijo /api a este archivo.
| Por eso usamos prefix('v1') aquí para obtener /api/v1/*
|--------------------------------------------------------------------------
*/

// Public routes
Route::post('/v1/register/coach', [AuthController::class, 'registerCoach']);
Route::post('/v1/login', [AuthController::class, 'login']);

Route::get('/v1/health', function () {
    return response()->json([
        'success' => true,
        'message' => 'COACHING API is running',
        'version' => '1.0.0',
        'timestamp' => now()->toIso8601String(),
    ]);
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Authentication
    Route::post('/v1/logout', [AuthController::class, 'logout']);
    Route::get('/v1/me', [AuthController::class, 'me']);
    Route::post('/v1/refresh', [AuthController::class, 'refresh']);
    
    // Athlete-specific endpoints
    Route::get('/v1/my/stats', [AthleteController::class, 'myStats']);
    Route::get('/v1/my/assignments', [AthleteController::class, 'myAssignments']);
    Route::get('/v1/my/assignments/{id}', [AthleteController::class, 'myAssignment']);
    Route::post('/v1/my/assignments/{id}/repeat', [AthleteController::class, 'repeatWorkout']);
    
    // Athletes CRUD
    Route::get('/v1/athletes', [AthleteController::class, 'index']);
    Route::post('/v1/athletes', [AthleteController::class, 'store']);
    Route::get('/v1/athletes/{athlete}', [AthleteController::class, 'show']);
    Route::put('/v1/athletes/{athlete}', [AthleteController::class, 'update']);
    Route::patch('/v1/athletes/{athlete}', [AthleteController::class, 'update']);
    Route::delete('/v1/athletes/{athlete}', [AthleteController::class, 'destroy']);
    
    // Workouts
    Route::get('/v1/workouts', [WorkoutController::class, 'index']);
    Route::post('/v1/workouts', [WorkoutController::class, 'store']);
    Route::get('/v1/workouts/{workout}', [WorkoutController::class, 'show']);
    Route::put('/v1/workouts/{workout}', [WorkoutController::class, 'update']);
    Route::patch('/v1/workouts/{workout}', [WorkoutController::class, 'update']);
    Route::delete('/v1/workouts/{workout}', [WorkoutController::class, 'destroy']);
    Route::get('/v1/benchmarks', [WorkoutController::class, 'benchmarks']);
    
    // Assignments
    Route::get('/v1/assignments', [AssignmentController::class, 'index']);
    Route::post('/v1/assignments', [AssignmentController::class, 'store']);
    Route::get('/v1/assignments/calendar', [AssignmentController::class, 'calendar']);
    Route::get('/v1/assignments/{assignment}', [AssignmentController::class, 'show']);
    Route::put('/v1/assignments/{assignment}', [AssignmentController::class, 'update']);
    Route::patch('/v1/assignments/{assignment}', [AssignmentController::class, 'update']);
    Route::delete('/v1/assignments/{assignment}', [AssignmentController::class, 'destroy']);
    Route::post('/v1/assignments/bulk', [AssignmentController::class, 'bulkAssign']);
    
    // Results
    Route::get('/v1/results', [ResultController::class, 'index']);
    Route::post('/v1/results', [ResultController::class, 'store']);
    Route::put('/v1/results/{result}', [ResultController::class, 'update']);
    Route::get('/v1/results/workout/{workoutId}/history', [ResultController::class, 'workoutHistory']);
    Route::get('/v1/personal-records', [ResultController::class, 'personalRecords']);
    
    // Analytics
    Route::get('/v1/analytics/dashboard', [AnalyticsController::class, 'dashboard']);
    Route::get('/v1/analytics/athlete/{athleteId}/progress', [AnalyticsController::class, 'athleteProgress']);
    Route::get('/v1/analytics/workout/{workoutId}/leaderboard', [AnalyticsController::class, 'workoutLeaderboard']);
});

Route::fallback(function () {
    return response()->json([
        'success' => false,
        'message' => 'Endpoint not found',
    ], 404);
});
