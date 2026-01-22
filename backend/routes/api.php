<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AthleteController;
use App\Http\Controllers\Api\WorkoutController;
use App\Http\Controllers\Api\AssignmentController;
use App\Http\Controllers\Api\ResultController;
use App\Http\Controllers\Api\MessageController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    
    // Athletes
    Route::get('/athletes', [AthleteController::class, 'index']);
    Route::post('/athletes', [AthleteController::class, 'store']);
    Route::get('/athletes/{id}', [AthleteController::class, 'show']);
    Route::put('/athletes/{id}', [AthleteController::class, 'update']);
    Route::delete('/athletes/{id}', [AthleteController::class, 'destroy']);
    Route::get('/athletes/{id}/progress', [AthleteController::class, 'progress']);
    
    // Workouts
    Route::get('/workouts', [WorkoutController::class, 'index']);
    Route::post('/workouts', [WorkoutController::class, 'store']);
    Route::get('/workouts/{id}', [WorkoutController::class, 'show']);
    Route::put('/workouts/{id}', [WorkoutController::class, 'update']);
    Route::delete('/workouts/{id}', [WorkoutController::class, 'destroy']);
    Route::get('/workouts/{id}/leaderboard', [WorkoutController::class, 'leaderboard']);
    
    // Assignments
    Route::get('/assignments', [AssignmentController::class, 'index']);
    Route::post('/assignments', [AssignmentController::class, 'store']);
    Route::post('/assignments/bulk', [AssignmentController::class, 'bulkStore']);
    Route::get('/assignments/{id}', [AssignmentController::class, 'show']);
    Route::put('/assignments/{id}', [AssignmentController::class, 'update']);
    Route::delete('/assignments/{id}', [AssignmentController::class, 'destroy']);
    
    // Results
    Route::get('/results', [ResultController::class, 'index']);
    Route::post('/results', [ResultController::class, 'store']);
    Route::get('/results/{id}', [ResultController::class, 'show']);
    
    // Analytics
    Route::get('/analytics/dashboard', [ResultController::class, 'dashboardStats']);
    
    // Messages
    Route::get('/messages', [MessageController::class, 'index']);
    Route::post('/messages', [MessageController::class, 'store']);
    Route::put('/messages/{id}/read', [MessageController::class, 'markAsRead']);
    Route::get('/messages/unread/count', [MessageController::class, 'unreadCount']);
    Route::get('/messages/conversations', [MessageController::class, 'conversations']);
});
