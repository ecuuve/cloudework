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
            ->with(['user', 'personalRecords'])
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

        // Calculate statistics
        $totalWorkouts = \App\Models\WorkoutResult::where('athlete_id', $athlete->id)->count();
        $totalPRs = $athlete->personalRecords()->count();
        
        $completedThisWeek = \App\Models\WorkoutAssignment::where('athlete_id', $athlete->id)
            ->where('is_completed', true)
            ->whereBetween('completed_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->count();
            
        $assignedThisWeek = \App\Models\WorkoutAssignment::where('athlete_id', $athlete->id)
            ->whereBetween('scheduled_date', [now()->startOfWeek(), now()->endOfWeek()])
            ->count();
            
        $completionRate = $assignedThisWeek > 0 ? round(($completedThisWeek / $assignedThisWeek) * 100) : 0;
        
        // Calculate current streak - simplified
        $results = \App\Models\WorkoutAssignment::where('athlete_id', $athlete->id)
            ->where('is_completed', true)
            ->whereNotNull('completed_at')
            ->orderBy('completed_at', 'desc')
            ->pluck('completed_at');
        
        $currentStreak = 0;
        if ($results->count() > 0) {
            $today = now()->startOfDay();
            $yesterday = now()->subDay()->startOfDay();
            $lastCompleted = \Carbon\Carbon::parse($results->first())->startOfDay();
            
            if ($lastCompleted->equalTo($today) || $lastCompleted->equalTo($yesterday)) {
                $currentStreak = 1;
                for ($i = 1; $i < $results->count(); $i++) {
                    $current = \Carbon\Carbon::parse($results[$i])->startOfDay();
                    $previous = \Carbon\Carbon::parse($results[$i - 1])->startOfDay();
                    $diff = $previous->diffInDays($current);
                    
                    if ($diff <= 1) {
                        $currentStreak++;
                    } else {
                        break;
                    }
                }
            }
        }

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
                        'total_workouts' => $totalWorkouts,
                        'total_prs' => $totalPRs,
                        'current_streak' => $currentStreak,
                        'completion_rate' => $completionRate,
                    ],
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

    /**
     * Get assignments for the authenticated athlete
     */
    public function myAssignments(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if (!$user->athlete) {
            return response()->json([
                'success' => false,
                'message' => 'Only athletes can access this endpoint',
            ], 403);
        }

        $query = $user->athlete->assignments()->with(['workout']);

        if ($request->has('scheduled_date')) {
            $query->whereDate('scheduled_date', $request->scheduled_date);
        }

        if ($request->has('date_from') && $request->has('date_to')) {
            $query->whereBetween('scheduled_date', [$request->date_from, $request->date_to]);
        }

        if ($request->has('is_completed')) {
            $query->where('is_completed', filter_var($request->is_completed, FILTER_VALIDATE_BOOLEAN));
        }

        $sortBy = $request->get('sort_by', 'scheduled_date');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        $assignments = $query->get();

        $formattedAssignments = $assignments->map(function ($assignment) {
            return [
                'id' => $assignment->id,
                'workout' => [
                    'id' => $assignment->workout->id,
                    'name' => $assignment->workout->name,
                    'type' => $assignment->workout->workout_type,
                    'difficulty' => $assignment->workout->difficulty_level,
                    'sections' => $assignment->workout->sections,
                ],
                'scheduled_date' => $assignment->scheduled_date->format('Y-m-d'),
                'is_completed' => $assignment->is_completed,
                'completed_at' => $assignment->completed_at ? $assignment->completed_at->format('Y-m-d H:i:s') : null,
                'priority' => $assignment->priority,
                'notes' => $assignment->notes,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'assignments' => $formattedAssignments,
                'total' => $assignments->count(),
            ],
        ]);
    }

    /**
     * Get single assignment for the authenticated athlete
     */
    public function myAssignment(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        
        if (!$user->athlete) {
            return response()->json([
                'success' => false,
                'message' => 'Only athletes can access this endpoint',
            ], 403);
        }

        $assignment = $user->athlete->assignments()
            ->with(['workout'])
            ->find($id);

        if (!$assignment) {
            return response()->json([
                'success' => false,
                'message' => 'Assignment not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'assignment' => [
                    'id' => $assignment->id,
                    'workout' => [
                        'id' => $assignment->workout->id,
                        'name' => $assignment->workout->name,
                        'type' => $assignment->workout->workout_type,
                        'difficulty' => $assignment->workout->difficulty_level,
                        'sections' => $assignment->workout->sections,
                    ],
                    'scheduled_date' => $assignment->scheduled_date->format('Y-m-d'),
                    'is_completed' => $assignment->is_completed,
                    'completed_at' => $assignment->completed_at ? $assignment->completed_at->format('Y-m-d H:i:s') : null,
                    'priority' => $assignment->priority,
                    'notes' => $assignment->notes,
                ],
            ],
        ]);
    }

    /**
     * Get dashboard statistics for the authenticated athlete
     */
    public function myStats(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if (!$user->athlete) {
            return response()->json([
                'success' => false,
                'message' => 'Only athletes can access this endpoint',
            ], 403);
        }

        $athlete = $user->athlete;
        $now = now();
        
        // Total workouts completed
        $totalWorkouts = $athlete->results()->count();
        
        // Total PRs
        $totalPRs = $athlete->personalRecords()->count();
        
        // Current streak (consecutive days with workouts)
        $currentStreak = $this->calculateStreak($athlete);
        
        // Longest streak
        $longestStreak = $this->calculateLongestStreak($athlete);
        
        // This week stats
        $weekStart = $now->copy()->startOfWeek();
        $weekEnd = $now->copy()->endOfWeek();
        $weekWorkouts = $athlete->results()
            ->whereBetween('completed_at', [$weekStart, $weekEnd])
            ->count();
        $weekAssignments = $athlete->assignments()
            ->whereBetween('scheduled_date', [$weekStart, $weekEnd])
            ->count();
        $weekCompletionRate = $weekAssignments > 0 
            ? round(($weekWorkouts / $weekAssignments) * 100) 
            : 0;
        
        // This month stats
        $monthStart = $now->copy()->startOfMonth();
        $monthEnd = $now->copy()->endOfMonth();
        $monthWorkouts = $athlete->results()
            ->whereBetween('completed_at', [$monthStart, $monthEnd])
            ->count();
        $monthPRs = $athlete->personalRecords()
            ->whereBetween('created_at', [$monthStart, $monthEnd])
            ->count();
        
        // Average workout time (in minutes)
        $avgTime = $athlete->results()
            ->whereNotNull('time_seconds')
            ->avg('time_seconds');
        $avgTimeMinutes = $avgTime ? round($avgTime / 60, 1) : 0;
        
        // RX rate (percentage of RX workouts)
        $rxCount = $athlete->results()->where('rx_or_scaled', 'rx')->count();
        $rxRate = $totalWorkouts > 0 
            ? round(($rxCount / $totalWorkouts) * 100) 
            : 0;
        
        // Average feeling rating
        $avgFeeling = $athlete->results()
            ->whereNotNull('feeling_rating')
            ->avg('feeling_rating');
        $avgFeelingRounded = $avgFeeling ? round($avgFeeling, 1) : 0;
        
        // Most common workout types
        $workoutTypes = $athlete->results()
            ->with('workoutAssignment.workout')
            ->get()
            ->pluck('workoutAssignment.workout.workout_type')
            ->filter()
            ->countBy()
            ->sortDesc()
            ->take(3)
            ->keys()
            ->toArray();
        
        // Recent PRs (last 5)
        $recentPRs = $athlete->personalRecords()
            ->with('workoutResult.workoutAssignment.workout')
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($pr) {
                return [
                    'workout_id' => $pr->workoutResult->workoutAssignment->workout->id ?? null,
                    'workout_name' => $pr->workoutResult->workoutAssignment->workout->name ?? 'Unknown',
                    'metric_type' => $pr->metric_type,
                    'metric_value' => $pr->metric_value,
                    'achieved_at' => $pr->created_at->format('Y-m-d'),
                ];
            });
        
        // Weekly activity (last 7 days with counts)
        $weeklyActivity = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = $now->copy()->subDays($i);
            $count = $athlete->results()
                ->whereDate('completed_at', $date)
                ->count();
            $weeklyActivity[] = [
                'date' => $date->format('Y-m-d'),
                'day' => $date->format('D'),
                'count' => $count,
            ];
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'overview' => [
                    'total_workouts' => $totalWorkouts,
                    'total_prs' => $totalPRs,
                    'current_streak' => $currentStreak,
                    'longest_streak' => $longestStreak,
                ],
                'this_week' => [
                    'workouts_completed' => $weekWorkouts,
                    'workouts_assigned' => $weekAssignments,
                    'completion_rate' => $weekCompletionRate,
                ],
                'this_month' => [
                    'workouts_completed' => $monthWorkouts,
                    'prs_achieved' => $monthPRs,
                ],
                'performance' => [
                    'avg_workout_time_minutes' => $avgTimeMinutes,
                    'rx_rate' => $rxRate,
                    'avg_feeling_rating' => $avgFeelingRounded,
                ],
                'insights' => [
                    'favorite_workout_types' => $workoutTypes,
                    'recent_prs' => $recentPRs,
                ],
                'weekly_activity' => $weeklyActivity,
            ],
        ]);
    }
    
    /**
     * Calculate current workout streak
     */
    private function calculateStreak($athlete): int
    {
        $streak = 0;
        $date = now()->startOfDay();
        
        while (true) {
            $hasWorkout = $athlete->results()
                ->whereDate('completed_at', $date)
                ->exists();
            
            if ($hasWorkout) {
                $streak++;
                $date->subDay();
            } else {
                // Allow one rest day
                $previousDay = $date->copy()->subDay();
                $hasPreviousWorkout = $athlete->results()
                    ->whereDate('completed_at', $previousDay)
                    ->exists();
                
                if ($hasPreviousWorkout && $streak > 0) {
                    $date->subDays(2);
                } else {
                    break;
                }
            }
            
            // Prevent infinite loop
            if ($date->diffInDays(now()) > 365) {
                break;
            }
        }
        
        return $streak;
    }
    
    /**
     * Calculate longest workout streak
     */
    private function calculateLongestStreak($athlete): int
    {
        $results = $athlete->results()
            ->whereNotNull('completed_at')
            ->orderBy('completed_at')
            ->get()
            ->pluck('completed_at')
            ->map(fn($date) => $date->startOfDay())
            ->unique();
        
        if ($results->isEmpty()) {
            return 0;
        }
        
        $longestStreak = 1;
        $currentStreak = 1;
        
        for ($i = 1; $i < $results->count(); $i++) {
            $diff = $results[$i]->diffInDays($results[$i - 1]);
            
            if ($diff <= 1) {
                $currentStreak++;
                $longestStreak = max($longestStreak, $currentStreak);
            } else {
                $currentStreak = 1;
            }
        }
        
        return $longestStreak;
    }
    
    /**
     * Allow athlete to self-assign a workout
     */
    public function selfAssignWorkout(Request $request): JsonResponse
    {
        $user = $request->user();
        $athlete = $user->athlete;
        
        if (!$athlete) {
            return response()->json([
                'success' => false,
                'message' => 'Only athletes can self-assign workouts',
            ], 403);
        }
        
        $validator = Validator::make($request->all(), [
            'workout_id' => 'required|exists:workouts,id',
            'scheduled_date' => 'nullable|date',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }
        
        try {
            // Create the assignment
            $assignment = \App\Models\WorkoutAssignment::create([
                'athlete_id' => $athlete->id,
                'workout_id' => $request->workout_id,
                'scheduled_date' => $request->scheduled_date ?? now()->toDateString(),
                'assigned_by_coach_id' => $athlete->coach_id,
                'priority' => 'medium',
                'is_completed' => false,
            ]);
            
            $assignment->load('workout');
            
            return response()->json([
                'success' => true,
                'message' => 'Workout asignado exitosamente',
                'data' => [
                    'assignment' => [
                        'id' => $assignment->id,
                        'workout_id' => $assignment->workout_id,
                        'scheduled_date' => $assignment->scheduled_date,
                        'workout' => [
                            'id' => $assignment->workout->id,
                            'name' => $assignment->workout->name,
                        ],
                    ],
                ],
            ], 201);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al asignar el workout',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
