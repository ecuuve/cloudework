<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Athlete;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class AthleteController extends Controller
{
    /**
     * Display a listing of athletes for the authenticated coach.
     */
    public function index(Request $request): JsonResponse
    {
        $coach = $request->user()->coach;

        if (!$coach) {
            return response()->json([
                'success' => false,
                'message' => 'Only coaches can access this endpoint',
            ], 403);
        }

        $query = $coach->athletes()->with('user');

        // Filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->get('per_page', 15);
        $athletes = $query->paginate($perPage);

        // Transform data
        $athletesData = $athletes->map(function ($athlete) {
            return [
                'id' => $athlete->id,
                'user' => [
                    'id' => $athlete->user->id,
                    'first_name' => $athlete->user->first_name,
                    'last_name' => $athlete->user->last_name,
                    'full_name' => $athlete->user->full_name,
                    'email' => $athlete->user->email,
                    'phone' => $athlete->user->phone,
                    'avatar_url' => $athlete->user->avatar_url,
                ],
                'date_of_birth' => $athlete->date_of_birth,
                'age' => $athlete->age,
                'gender' => $athlete->gender,
                'height_cm' => $athlete->height_cm,
                'weight_kg' => $athlete->weight_kg,
                'goals' => $athlete->goals,
                'status' => $athlete->status,
                'start_date' => $athlete->start_date,
                'statistics' => [
                    'total_workouts' => $athlete->total_workouts,
                    'total_prs' => $athlete->total_prs,
                    'current_streak' => $athlete->current_streak,
                    'completion_rate' => $athlete->completion_rate,
                ],
                'last_workout_date' => $athlete->results()->latest('completed_at')->value('completed_at'),
                'created_at' => $athlete->created_at,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'athletes' => $athletesData,
                'pagination' => [
                    'current_page' => $athletes->currentPage(),
                    'last_page' => $athletes->lastPage(),
                    'per_page' => $athletes->perPage(),
                    'total' => $athletes->total(),
                ],
            ],
        ]);
    }

    /**
     * Store a newly created athlete.
     */
    public function store(Request $request): JsonResponse
    {
        $coach = $request->user()->coach;

        if (!$coach) {
            return response()->json([
                'success' => false,
                'message' => 'Only coaches can create athletes',
            ], 403);
        }

        // Check if coach can add more athletes
        if (!$coach->canAddAthletes()) {
            return response()->json([
                'success' => false,
                'message' => 'You have reached your maximum number of athletes. Please upgrade your plan.',
                'data' => [
                    'max_athletes' => $coach->max_athletes,
                    'current_athletes' => $coach->athletes()->count(),
                ],
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'phone' => 'nullable|string|max:20',
            'date_of_birth' => 'nullable|date|before:today',
            'gender' => 'nullable|in:male,female,other,prefer_not_to_say',
            'height_cm' => 'nullable|numeric|min:0|max:300',
            'weight_kg' => 'nullable|numeric|min:0|max:500',
            'goals' => 'nullable|string',
            'medical_notes' => 'nullable|string',
            'emergency_contact_name' => 'nullable|string|max:150',
            'emergency_contact_phone' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Create user
            $user = User::create([
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => 'athlete',
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'phone' => $request->phone,
            ]);

            // Create athlete profile
            $athlete = Athlete::create([
                'user_id' => $user->id,
                'coach_id' => $coach->id,
                'date_of_birth' => $request->date_of_birth,
                'gender' => $request->gender,
                'height_cm' => $request->height_cm,
                'weight_kg' => $request->weight_kg,
                'goals' => $request->goals,
                'medical_notes' => $request->medical_notes,
                'emergency_contact_name' => $request->emergency_contact_name,
                'emergency_contact_phone' => $request->emergency_contact_phone,
                'start_date' => now(),
                'status' => 'active',
            ]);

            DB::commit();

            $athlete->load('user');

            return response()->json([
                'success' => true,
                'message' => 'Athlete created successfully',
                'data' => [
                    'athlete' => [
                        'id' => $athlete->id,
                        'user' => [
                            'id' => $user->id,
                            'email' => $user->email,
                            'first_name' => $user->first_name,
                            'last_name' => $user->last_name,
                            'full_name' => $user->full_name,
                        ],
                        'status' => $athlete->status,
                        'start_date' => $athlete->start_date,
                    ],
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create athlete',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified athlete.
     */
    public function show(Request $request, $id): JsonResponse
    {
        $coach = $request->user()->coach;

        if (!$coach) {
            return response()->json([
                'success' => false,
                'message' => 'Only coaches can access this endpoint',
            ], 403);
        }

        $athlete = $coach->athletes()
            ->with(['user', 'personalRecords', 'groups'])
            ->find($id);

        if (!$athlete) {
            return response()->json([
                'success' => false,
                'message' => 'Athlete not found',
            ], 404);
        }

        // Get recent results
        $recentResults = $athlete->results()
            ->with('workout')
            ->latest('completed_at')
            ->limit(10)
            ->get()
            ->map(function ($result) {
                return [
                    'id' => $result->id,
                    'workout' => [
                        'id' => $result->workout->id,
                        'name' => $result->workout->name,
                    ],
                    'completed_at' => $result->completed_at,
                    'time_seconds' => $result->time_seconds,
                    'formatted_time' => $result->formatted_time,
                    'rounds_completed' => $result->rounds_completed,
                    'rx_or_scaled' => $result->rx_or_scaled,
                    'is_pr' => $result->is_pr,
                ];
            });

        // Get recent PRs
        $recentPRs = $athlete->personalRecords()
            ->latest('achieved_at')
            ->limit(5)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'athlete' => [
                    'id' => $athlete->id,
                    'user' => [
                        'id' => $athlete->user->id,
                        'email' => $athlete->user->email,
                        'first_name' => $athlete->user->first_name,
                        'last_name' => $athlete->user->last_name,
                        'full_name' => $athlete->user->full_name,
                        'phone' => $athlete->user->phone,
                        'avatar_url' => $athlete->user->avatar_url,
                    ],
                    'date_of_birth' => $athlete->date_of_birth,
                    'age' => $athlete->age,
                    'gender' => $athlete->gender,
                    'height_cm' => $athlete->height_cm,
                    'weight_kg' => $athlete->weight_kg,
                    'goals' => $athlete->goals,
                    'medical_notes' => $athlete->medical_notes,
                    'emergency_contact_name' => $athlete->emergency_contact_name,
                    'emergency_contact_phone' => $athlete->emergency_contact_phone,
                    'status' => $athlete->status,
                    'start_date' => $athlete->start_date,
                    'statistics' => [
                        'total_workouts' => $athlete->total_workouts,
                        'total_prs' => $athlete->total_prs,
                        'current_streak' => $athlete->current_streak,
                        'completion_rate' => $athlete->completion_rate,
                    ],
                    'groups' => $athlete->groups->map(fn($g) => [
                        'id' => $g->id,
                        'name' => $g->name,
                        'color' => $g->color,
                    ]),
                    'recent_results' => $recentResults,
                    'recent_prs' => $recentPRs,
                    'created_at' => $athlete->created_at,
                ],
            ],
        ]);
    }

    /**
     * Update the specified athlete.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $coach = $request->user()->coach;

        if (!$coach) {
            return response()->json([
                'success' => false,
                'message' => 'Only coaches can update athletes',
            ], 403);
        }

        $athlete = $coach->athletes()->find($id);

        if (!$athlete) {
            return response()->json([
                'success' => false,
                'message' => 'Athlete not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'first_name' => 'sometimes|string|max:100',
            'last_name' => 'sometimes|string|max:100',
            'phone' => 'nullable|string|max:20',
            'date_of_birth' => 'nullable|date|before:today',
            'gender' => 'nullable|in:male,female,other,prefer_not_to_say',
            'height_cm' => 'nullable|numeric|min:0|max:300',
            'weight_kg' => 'nullable|numeric|min:0|max:500',
            'goals' => 'nullable|string',
            'medical_notes' => 'nullable|string',
            'emergency_contact_name' => 'nullable|string|max:150',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'status' => 'sometimes|in:active,inactive,on_hold',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Update user data
            if ($request->has('first_name') || $request->has('last_name') || $request->has('phone')) {
                $athlete->user->update($request->only(['first_name', 'last_name', 'phone']));
            }

            // Update athlete data
            $athlete->update($request->only([
                'date_of_birth',
                'gender',
                'height_cm',
                'weight_kg',
                'goals',
                'medical_notes',
                'emergency_contact_name',
                'emergency_contact_phone',
                'status',
            ]));

            DB::commit();

            $athlete->load('user');

            return response()->json([
                'success' => true,
                'message' => 'Athlete updated successfully',
                'data' => [
                    'athlete' => [
                        'id' => $athlete->id,
                        'user' => [
                            'first_name' => $athlete->user->first_name,
                            'last_name' => $athlete->user->last_name,
                            'full_name' => $athlete->user->full_name,
                            'phone' => $athlete->user->phone,
                        ],
                        'status' => $athlete->status,
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update athlete',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified athlete.
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        $coach = $request->user()->coach;

        if (!$coach) {
            return response()->json([
                'success' => false,
                'message' => 'Only coaches can delete athletes',
            ], 403);
        }

        $athlete = $coach->athletes()->find($id);

        if (!$athlete) {
            return response()->json([
                'success' => false,
                'message' => 'Athlete not found',
            ], 404);
        }

        try {
            DB::beginTransaction();

            // Delete user (will cascade delete athlete due to foreign key)
            $athlete->user->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Athlete deleted successfully',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete athlete',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
