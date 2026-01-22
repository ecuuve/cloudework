<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WorkoutResult;
use App\Models\WorkoutAssignment;
use App\Models\PersonalRecord;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ResultController extends Controller
{
    /**
     * Submit a workout result.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'assignment_id' => 'required|exists:workout_assignments,id',
            'completed_at' => 'required|date',
            'time_seconds' => 'nullable|integer|min:0',
            'rounds_completed' => 'nullable|integer|min:0',
            'reps_completed' => 'nullable|integer|min:0',
            'weight_used' => 'nullable|array',
            'rx_or_scaled' => 'required|in:rx,scaled',
            'feeling_rating' => 'nullable|integer|min:1|max:5',
            'notes' => 'nullable|string',
            'video_url' => 'nullable|url',
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

            $assignment = WorkoutAssignment::with(['workout', 'athlete'])->find($request->assignment_id);

            if (!$assignment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Assignment not found',
                ], 404);
            }

            // Check if result already exists
            if ($assignment->result) {
                return response()->json([
                    'success' => false,
                    'message' => 'Result already submitted for this assignment',
                ], 422);
            }

            // Check for Personal Record
            $isPR = $this->checkForPersonalRecord(
                $assignment->athlete_id,
                $assignment->workout_id,
                $request->time_seconds,
                $request->rx_or_scaled
            );

            // Create result
            $result = WorkoutResult::create([
                'assignment_id' => $assignment->id,
                'athlete_id' => $assignment->athlete_id,
                'workout_id' => $assignment->workout_id,
                'completed_at' => $request->completed_at,
                'time_seconds' => $request->time_seconds,
                'rounds_completed' => $request->rounds_completed,
                'reps_completed' => $request->reps_completed,
                'weight_used' => $request->weight_used,
                'rx_or_scaled' => $request->rx_or_scaled,
                'feeling_rating' => $request->feeling_rating,
                'notes' => $request->notes,
                'video_url' => $request->video_url,
                'is_pr' => $isPR,
            ]);

            // Mark assignment as completed
            $assignment->update(['is_completed' => true]);

            // If it's a PR, create Personal Record entry
            if ($isPR && $request->time_seconds) {
                PersonalRecord::create([
                    'athlete_id' => $assignment->athlete_id,
                    'movement_name' => $assignment->workout->name,
                    'record_type' => 'time',
                    'value' => $request->time_seconds,
                    'unit' => 'seconds',
                    'workout_result_id' => $result->id,
                    'achieved_at' => $request->completed_at,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $isPR ? '🎉 New Personal Record!' : 'Result submitted successfully',
                'data' => [
                    'result' => [
                        'id' => $result->id,
                        'workout_name' => $assignment->workout->name,
                        'time_seconds' => $result->time_seconds,
                        'formatted_time' => $result->formatted_time,
                        'rx_or_scaled' => $result->rx_or_scaled,
                        'is_pr' => $result->is_pr,
                        'completed_at' => $result->completed_at->format('Y-m-d H:i'),
                    ],
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit result',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get athlete's results.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // If coach, get athlete_id from request
        // If athlete, get own results
        if ($user->isCoach()) {
            // athlete_id is now optional for coaches
            if ($request->has('athlete_id')) {
                $validator = Validator::make($request->all(), [
                    'athlete_id' => 'required|exists:athletes,id',
                ]);

                if ($validator->fails()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid athlete ID',
                        'errors' => $validator->errors(),
                    ], 422);
                }

                $athleteId = $request->athlete_id;
                
                // Verify athlete belongs to coach
                $athlete = $user->coach->athletes()->find($athleteId);
                if (!$athlete) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Athlete not found',
                    ], 404);
                }
            } else {
                // No athlete_id provided, return all results from coach's athletes
                $athleteId = null;
            }
        } else {
            $athleteId = $user->athlete->id;
        }

        $query = WorkoutResult::with(['workout', 'assignment', 'athlete.user']);
        
        if ($athleteId !== null) {
            $query->where('athlete_id', $athleteId);
        } elseif ($user->isCoach()) {
            // Coach viewing all athletes
            $athleteIds = $user->coach->athletes()->pluck('id');
            $query->whereIn('athlete_id', $athleteIds);
        }

        // Filters
        if ($request->has('workout_id')) {
            $query->where('workout_id', $request->workout_id);
        }

        if ($request->has('is_pr')) {
            $query->where('is_pr', filter_var($request->is_pr, FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->has('rx_or_scaled')) {
            $query->where('rx_or_scaled', $request->rx_or_scaled);
        }

        if ($request->has('date_from') && $request->has('date_to')) {
            $query->whereBetween('completed_at', [$request->date_from, $request->date_to]);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'completed_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->get('per_page', 20);
        $results = $query->paginate($perPage);

        // Format response
        $formattedResults = $results->map(function ($result) {
            return [
                'id' => $result->id,
                'workout' => [
                    'id' => $result->workout->id,
                    'name' => $result->workout->name,
                    'type' => $result->workout->workout_type,
                ],
                'completed_at' => $result->completed_at->format('Y-m-d H:i'),
                'time_seconds' => $result->time_seconds,
                'formatted_time' => $result->formatted_time,
                'rounds_completed' => $result->rounds_completed,
                'reps_completed' => $result->reps_completed,
                'weight_used' => $result->weight_used,
                'rx_or_scaled' => $result->rx_or_scaled,
                'feeling_rating' => $result->feeling_rating,
                'is_pr' => $result->is_pr,
                'notes' => $result->notes,
                'video_url' => $result->video_url,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'results' => $formattedResults,
                'pagination' => [
                    'current_page' => $results->currentPage(),
                    'total_pages' => $results->lastPage(),
                    'total' => $results->total(),
                    'per_page' => $results->perPage(),
                ],
            ],
        ]);
    }

    /**
     * Get workout history for specific workout.
     */
    public function workoutHistory(Request $request, int $workoutId): JsonResponse
    {
        $user = $request->user();
        
        if ($user->isCoach()) {
            $validator = Validator::make($request->all(), [
                'athlete_id' => 'required|exists:athletes,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Athlete ID required',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $athleteId = $request->athlete_id;
        } else {
            $athleteId = $user->athlete->id;
        }

        $results = WorkoutResult::where('athlete_id', $athleteId)
            ->where('workout_id', $workoutId)
            ->orderBy('completed_at', 'desc')
            ->get()
            ->map(function ($result) {
                return [
                    'id' => $result->id,
                    'completed_at' => $result->completed_at->format('Y-m-d'),
                    'time_seconds' => $result->time_seconds,
                    'formatted_time' => $result->formatted_time,
                    'rx_or_scaled' => $result->rx_or_scaled,
                    'is_pr' => $result->is_pr,
                    'feeling_rating' => $result->feeling_rating,
                ];
            });

        // Calculate stats
        $bestTime = $results->where('rx_or_scaled', 'rx')
            ->whereNotNull('time_seconds')
            ->min('time_seconds');

        $averageTime = $results->where('rx_or_scaled', 'rx')
            ->whereNotNull('time_seconds')
            ->avg('time_seconds');

        return response()->json([
            'success' => true,
            'data' => [
                'results' => $results,
                'statistics' => [
                    'total_attempts' => $results->count(),
                    'rx_attempts' => $results->where('rx_or_scaled', 'rx')->count(),
                    'best_time_seconds' => $bestTime,
                    'average_time_seconds' => $averageTime ? round($averageTime) : null,
                    'prs' => $results->where('is_pr', true)->count(),
                ],
            ],
        ]);
    }

    /**
     * Get athlete's personal records.
     */
    public function personalRecords(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if ($user->isCoach()) {
            $validator = Validator::make($request->all(), [
                'athlete_id' => 'required|exists:athletes,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Athlete ID required',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $athleteId = $request->athlete_id;
        } else {
            $athleteId = $user->athlete->id;
        }

        $prs = PersonalRecord::where('athlete_id', $athleteId)
            ->orderBy('achieved_at', 'desc')
            ->get()
            ->groupBy('movement_name')
            ->map(function ($records, $movement) {
                $latest = $records->first();
                return [
                    'movement_name' => $movement,
                    'record_type' => $latest->record_type,
                    'current_record' => [
                        'value' => $latest->value,
                        'unit' => $latest->unit,
                        'achieved_at' => $latest->achieved_at->format('Y-m-d'),
                    ],
                    'history' => $records->map(function ($pr) {
                        return [
                            'value' => $pr->value,
                            'unit' => $pr->unit,
                            'achieved_at' => $pr->achieved_at->format('Y-m-d'),
                        ];
                    })->values(),
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'data' => [
                'personal_records' => $prs,
                'total_movements' => $prs->count(),
            ],
        ]);
    }

    /**
     * Check if result is a personal record.
     */
    private function checkForPersonalRecord(
        int $athleteId,
        int $workoutId,
        ?int $timeSeconds,
        string $rxOrScaled
    ): bool {
        if (!$timeSeconds || $rxOrScaled !== 'rx') {
            return false;
        }

        $bestPreviousTime = WorkoutResult::where('athlete_id', $athleteId)
            ->where('workout_id', $workoutId)
            ->where('rx_or_scaled', 'rx')
            ->whereNotNull('time_seconds')
            ->min('time_seconds');

        return !$bestPreviousTime || $timeSeconds < $bestPreviousTime;
    }

    /**
     * Update a result.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $result = WorkoutResult::find($id);

        if (!$result) {
            return response()->json([
                'success' => false,
                'message' => 'Result not found',
            ], 404);
        }

        // Only allow athlete or their coach to update
        if ($user->isAthlete() && $result->athlete_id !== $user->athlete->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        if ($user->isCoach()) {
            $athlete = $user->coach->athletes()->find($result->athlete_id);
            if (!$athlete) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 403);
            }
        }

        $validator = Validator::make($request->all(), [
            'feeling_rating' => 'sometimes|integer|min:1|max:5',
            'notes' => 'nullable|string',
            'video_url' => 'nullable|url',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $result->update($request->only(['feeling_rating', 'notes', 'video_url']));

            return response()->json([
                'success' => true,
                'message' => 'Result updated successfully',
                'data' => [
                    'result' => [
                        'id' => $result->id,
                        'feeling_rating' => $result->feeling_rating,
                        'notes' => $result->notes,
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update result',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get dashboard statistics
     */
    public function dashboardStats(Request $request): JsonResponse
    {
        $user = $request->user();

        try {
            // Get coach's athletes
            if (!$user->isCoach() || !$user->coach) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only coaches can access dashboard stats',
                ], 403);
            }

            $coach = $user->coach;
            $athleteIds = $coach->athletes()->pluck('id');

            // Total athletes
            $totalAthletes = $athleteIds->count();

            // Workouts this week
            $startOfWeek = now()->startOfWeek();
            $endOfWeek = now()->endOfWeek();
            $workoutsThisWeek = WorkoutResult::whereIn('athlete_id', $athleteIds)
                ->whereBetween('completed_at', [$startOfWeek, $endOfWeek])
                ->count();

            // Completion rate (workouts completed vs assigned this month)
            $startOfMonth = now()->startOfMonth();
            $endOfMonth = now()->endOfMonth();
            
            $assignedThisMonth = \App\Models\WorkoutAssignment::whereIn('athlete_id', $athleteIds)
                ->whereBetween('scheduled_date', [$startOfMonth->format('Y-m-d'), $endOfMonth->format('Y-m-d')])
                ->count();
            
            $completedThisMonth = \App\Models\WorkoutAssignment::whereIn('athlete_id', $athleteIds)
                ->whereBetween('scheduled_date', [$startOfMonth->format('Y-m-d'), $endOfMonth->format('Y-m-d')])
                ->where('is_completed', true)
                ->count();

            $completionRate = $assignedThisMonth > 0 
                ? round(($completedThisMonth / $assignedThisMonth) * 100) 
                : 0;

            // PRs this month
            $prsThisMonth = WorkoutResult::whereIn('athlete_id', $athleteIds)
                ->where('is_pr', true)
                ->whereBetween('completed_at', [$startOfMonth, $endOfMonth])
                ->count();

            // Recent activity
            $recentResults = WorkoutResult::with(['athlete.user', 'workout'])
                ->whereIn('athlete_id', $athleteIds)
                ->orderBy('completed_at', 'desc')
                ->take(10)
                ->get()
                ->map(function ($result) {
                    return [
                        'athlete_name' => $result->athlete->user->first_name . ' ' . $result->athlete->user->last_name,
                        'workout_name' => $result->workout->name,
                        'completed_at' => $result->completed_at->format('Y-m-d H:i'),
                        'is_pr' => $result->is_pr,
                        'time_seconds' => $result->time_seconds,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'statistics' => [
                        'total_athletes' => $totalAthletes,
                        'workouts_this_week' => $workoutsThisWeek,
                        'completion_rate' => $completionRate,
                        'prs_this_month' => $prsThisMonth,
                    ],
                    'recent_activity' => $recentResults,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch dashboard stats',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
