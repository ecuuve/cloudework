<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Coach;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register a new coach.
     */
    public function registerCoach(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:users,email|max:255',
            'password' => 'required|string|min:8|confirmed',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'phone' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Create user
            $user = User::create([
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => 'coach',
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'phone' => $request->phone,
            ]);

            // Create coach profile with trial subscription
            $coach = Coach::create([
                'user_id' => $user->id,
                'subscription_status' => 'trial',
                'subscription_plan' => 'basic',
                'subscription_start_date' => now(),
                'subscription_end_date' => now()->addDays(14),
                'max_athletes' => 5, // Free trial limit
            ]);

            // Generate token
            $token = $user->createToken('auth-token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Coach registered successfully',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'email' => $user->email,
                        'first_name' => $user->first_name,
                        'last_name' => $user->last_name,
                        'role' => $user->role,
                    ],
                    'coach' => [
                        'id' => $coach->id,
                        'subscription_status' => $coach->subscription_status,
                        'subscription_plan' => $coach->subscription_plan,
                        'max_athletes' => $coach->max_athletes,
                        'trial_ends_at' => $coach->subscription_end_date,
                    ],
                    'token' => $token,
                ],
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Registration failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Login user.
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Find user
        $user = User::where('email', $request->email)->first();

        // Check if user exists and password is correct
        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Check if user is active
        if (!$user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Your account has been deactivated.',
            ], 403);
        }

        // Delete old tokens
        $user->tokens()->delete();

        // Create new token
        $token = $user->createToken('auth-token')->plainTextToken;

        // Load relationships
        $user->load($user->isCoach() ? 'coach' : 'athlete');

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'full_name' => $user->full_name,
                    'role' => $user->role,
                    'avatar_url' => $user->avatar_url,
                ],
                'profile' => $user->isCoach() ? [
                    'type' => 'coach',
                    'id' => $user->coach->id,
                    'subscription_status' => $user->coach->subscription_status,
                    'subscription_plan' => $user->coach->subscription_plan,
                    'max_athletes' => $user->coach->max_athletes,
                    'remaining_slots' => $user->coach->getRemainingSlots(),
                ] : [
                    'type' => 'athlete',
                    'id' => $user->athlete->id,
                    'coach_id' => $user->athlete->coach_id,
                    'status' => $user->athlete->status,
                ],
                'token' => $token,
            ],
        ]);
    }

    /**
     * Logout user (revoke token).
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ]);
    }

    /**
     * Get authenticated user.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->load($user->isCoach() ? 'coach' : 'athlete');

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'full_name' => $user->full_name,
                    'role' => $user->role,
                    'phone' => $user->phone,
                    'avatar_url' => $user->avatar_url,
                    'timezone' => $user->timezone,
                    'language' => $user->language,
                ],
                'profile' => $user->isCoach() ? [
                    'type' => 'coach',
                    'id' => $user->coach->id,
                    'bio' => $user->coach->bio,
                    'certification_level' => $user->coach->certification_level,
                    'years_experience' => $user->coach->years_experience,
                    'specialties' => $user->coach->specialties,
                    'subscription_status' => $user->coach->subscription_status,
                    'subscription_plan' => $user->coach->subscription_plan,
                    'max_athletes' => $user->coach->max_athletes,
                    'active_athletes' => $user->coach->athletes()->active()->count(),
                    'remaining_slots' => $user->coach->getRemainingSlots(),
                ] : [
                    'type' => 'athlete',
                    'id' => $user->athlete->id,
                    'coach_id' => $user->athlete->coach_id,
                    'status' => $user->athlete->status,
                    'goals' => $user->athlete->goals,
                    'age' => $user->athlete->age,
                    'total_workouts' => $user->athlete->total_workouts,
                    'total_prs' => $user->athlete->total_prs,
                    'current_streak' => $user->athlete->current_streak,
                    'completion_rate' => $user->athlete->completion_rate,
                ],
            ],
        ]);
    }

    /**
     * Refresh authentication token.
     */
    public function refresh(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Delete current token
        $request->user()->currentAccessToken()->delete();
        
        // Create new token
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Token refreshed successfully',
            'data' => [
                'token' => $token,
            ],
        ]);
    }
}
