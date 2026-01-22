<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Workout;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class WorkoutController extends Controller
{
    /**
     * Display a listing of workouts.
     */
    public function index(Request $request): JsonResponse
    {
        $coach = $request->user()->coach;

        $query = Workout::query();

        // Show public benchmarks + coach's own workouts
        $query->where(function($q) use ($coach) {
            $q->where('is_public', true)
              ->orWhere('created_by_coach_id', $coach?->id);
        });

        // Filters
        if ($request->has('workout_type')) {
            $query->where('workout_type', $request->workout_type);
        }

        if ($request->has('difficulty_level')) {
            $query->where('difficulty_level', $request->difficulty_level);
        }

        if ($request->has('benchmark_category')) {
            $query->where('benchmark_category', $request->benchmark_category);
        }

        if ($request->has('is_benchmark')) {
            $query->where('is_benchmark', filter_var($request->is_benchmark, FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->has('tags')) {
            $tags = is_array($request->tags) ? $request->tags : explode(',', $request->tags);
            foreach ($tags as $tag) {
                $query->whereJsonContains('tags', trim($tag));
            }
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        
        if ($sortBy === 'popularity') {
            $query->withCount('assignments')
                  ->orderBy('assignments_count', $sortOrder);
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

        // Pagination
        $perPage = $request->get('per_page', 20);
        $workouts = $query->paginate($perPage);

        // Format response
        $formattedWorkouts = $workouts->map(function ($workout) {
            return [
                'id' => $workout->id,
                'name' => $workout->name,
                'slug' => $workout->slug,
                'description' => $workout->description,
                'workout_type' => $workout->workout_type,
                'benchmark_category' => $workout->benchmark_category,
                'difficulty_level' => $workout->difficulty_level,
                'is_benchmark' => $workout->is_benchmark,
                'is_public' => $workout->is_public,
                'estimated_duration_minutes' => $workout->estimated_duration_minutes,
                'format_display' => $workout->format_display,
                'equipment_needed' => $workout->equipment_needed,
                'tags' => $workout->tags,
                'times_assigned' => $workout->times_assigned,
                'average_time' => $workout->average_time,
                'created_at' => $workout->created_at->format('Y-m-d'),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'workouts' => $formattedWorkouts,
                'pagination' => [
                    'current_page' => $workouts->currentPage(),
                    'total_pages' => $workouts->lastPage(),
                    'total' => $workouts->total(),
                    'per_page' => $workouts->perPage(),
                ],
            ],
        ]);
    }

    /**
     * Store a newly created workout.
     */
    public function store(Request $request): JsonResponse
    {
        $coach = $request->user()->coach;

        if (!$coach) {
            return response()->json([
                'success' => false,
                'message' => 'Only coaches can create workouts',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:200',
            'description' => 'required|string',
            'workout_type' => 'required|in:metcon,strength,skill,benchmark,custom',
            'difficulty_level' => 'required|in:beginner,intermediate,rx,advanced',
            'estimated_duration_minutes' => 'nullable|integer|min:1|max:180',
            'workout_structure' => 'required|array',
            'workout_structure.format' => 'required|in:for_time,amrap,emom,tabata,rounds,chipper,strength',
            'scaling_options' => 'nullable|array',
            'equipment_needed' => 'nullable|array',
            'tags' => 'nullable|array',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $workout = Workout::create([
                'name' => $request->name,
                'slug' => Str::slug($request->name),
                'description' => $request->description,
                'workout_type' => $request->workout_type,
                'benchmark_category' => $request->benchmark_category,
                'difficulty_level' => $request->difficulty_level,
                'created_by_coach_id' => $coach->id,
                'is_public' => false, // Coach's personal workouts are private by default
                'is_benchmark' => false,
                'estimated_duration_minutes' => $request->estimated_duration_minutes,
                'workout_structure' => $request->workout_structure,
                'scaling_options' => $request->scaling_options,
                'equipment_needed' => $request->equipment_needed,
                'tags' => $request->tags,
                'notes' => $request->notes,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Workout created successfully',
                'data' => [
                    'workout' => [
                        'id' => $workout->id,
                        'name' => $workout->name,
                        'slug' => $workout->slug,
                        'workout_type' => $workout->workout_type,
                        'difficulty_level' => $workout->difficulty_level,
                    ],
                ],
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create workout',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified workout.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $coach = $request->user()->coach;

        $workout = Workout::where(function($q) use ($coach) {
            $q->where('is_public', true)
              ->orWhere('created_by_coach_id', $coach?->id);
        })->find($id);

        if (!$workout) {
            return response()->json([
                'success' => false,
                'message' => 'Workout not found',
            ], 404);
        }

        // Get usage stats
        $totalAssignments = $workout->assignments()->count();
        $totalCompletions = $workout->results()->count();
        $averageTime = $workout->average_time;
        $fastestTime = $workout->results()->whereNotNull('time_seconds')->min('time_seconds');
        
        return response()->json([
            'success' => true,
            'data' => [
                'workout' => [
                    'id' => $workout->id,
                    'name' => $workout->name,
                    'slug' => $workout->slug,
                    'description' => $workout->description,
                    'workout_type' => $workout->workout_type,
                    'benchmark_category' => $workout->benchmark_category,
                    'difficulty_level' => $workout->difficulty_level,
                    'is_benchmark' => $workout->is_benchmark,
                    'is_public' => $workout->is_public,
                    'estimated_duration_minutes' => $workout->estimated_duration_minutes,
                    'workout_structure' => $workout->workout_structure,
                    'scaling_options' => $workout->scaling_options,
                    'equipment_needed' => $workout->equipment_needed,
                    'tags' => $workout->tags,
                    'notes' => $workout->notes,
                    'statistics' => [
                        'times_assigned' => $totalAssignments,
                        'times_completed' => $totalCompletions,
                        'average_time_seconds' => $averageTime,
                        'fastest_time_seconds' => $fastestTime,
                    ],
                    'created_at' => $workout->created_at->format('Y-m-d'),
                ],
            ],
        ]);
    }

    /**
     * Update the specified workout.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $coach = $request->user()->coach;

        if (!$coach) {
            return response()->json([
                'success' => false,
                'message' => 'Only coaches can update workouts',
            ], 403);
        }

        // Can only update own workouts, not public benchmarks
        $workout = Workout::where('created_by_coach_id', $coach->id)
            ->where('is_benchmark', false)
            ->find($id);

        if (!$workout) {
            return response()->json([
                'success' => false,
                'message' => 'Workout not found or you do not have permission to edit it',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:200',
            'description' => 'sometimes|string',
            'workout_type' => 'sometimes|in:metcon,strength,skill,benchmark,custom',
            'difficulty_level' => 'sometimes|in:beginner,intermediate,rx,advanced',
            'estimated_duration_minutes' => 'nullable|integer|min:1|max:180',
            'workout_structure' => 'sometimes|array',
            'scaling_options' => 'nullable|array',
            'equipment_needed' => 'nullable|array',
            'tags' => 'nullable|array',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $workout->update($request->only([
                'name',
                'description',
                'workout_type',
                'difficulty_level',
                'estimated_duration_minutes',
                'workout_structure',
                'scaling_options',
                'equipment_needed',
                'tags',
                'notes',
            ]));

            // Update slug if name changed
            if ($request->has('name')) {
                $workout->slug = Str::slug($request->name);
                $workout->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Workout updated successfully',
                'data' => [
                    'workout' => [
                        'id' => $workout->id,
                        'name' => $workout->name,
                        'slug' => $workout->slug,
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update workout',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified workout.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $coach = $request->user()->coach;

        if (!$coach) {
            return response()->json([
                'success' => false,
                'message' => 'Only coaches can delete workouts',
            ], 403);
        }

        // Can only delete own workouts, not benchmarks
        $workout = Workout::where('created_by_coach_id', $coach->id)
            ->where('is_benchmark', false)
            ->find($id);

        if (!$workout) {
            return response()->json([
                'success' => false,
                'message' => 'Workout not found or cannot be deleted',
            ], 404);
        }

        // Check if workout has been assigned
        if ($workout->assignments()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete workout that has been assigned to athletes',
                'assignments_count' => $workout->assignments()->count(),
            ], 422);
        }

        try {
            $workout->delete();

            return response()->json([
                'success' => true,
                'message' => 'Workout deleted successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete workout',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get benchmarks by category.
     */
    public function benchmarks(Request $request): JsonResponse
    {
        $category = $request->get('category'); // girl, hero, open, etc.

        $query = Workout::where('is_benchmark', true);

        if ($category) {
            $query->where('benchmark_category', $category);
        }

        $benchmarks = $query->orderBy('name')->get()->map(function ($workout) {
            return [
                'id' => $workout->id,
                'name' => $workout->name,
                'category' => $workout->benchmark_category,
                'description' => $workout->description,
                'difficulty_level' => $workout->difficulty_level,
                'estimated_duration_minutes' => $workout->estimated_duration_minutes,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'benchmarks' => $benchmarks,
                'total' => $benchmarks->count(),
            ],
        ]);
    }

    /**
     * Get leaderboard for a specific workout
     */
    public function leaderboard(Request $request, $id): JsonResponse
    {
        $workout = Workout::find($id);

        if (!$workout) {
            return response()->json([
                'success' => false,
                'message' => 'Workout not found',
            ], 404);
        }

        // Get all results for this workout, sorted by time (fastest first)
        $results = \App\Models\WorkoutResult::with(['athlete.user'])
            ->where('workout_id', $id)
            ->where('time_seconds', '>', 0) // Only results with time
            ->orderBy('time_seconds', 'asc')
            ->get();

        $leaderboard = $results->map(function ($result, $index) {
            return [
                'position' => $index + 1,
                'athlete_id' => $result->athlete_id,
                'athlete_name' => $result->athlete->user->first_name . ' ' . $result->athlete->user->last_name,
                'time_seconds' => $result->time_seconds,
                'rx_or_scaled' => $result->rx_or_scaled,
                'completed_at' => $result->completed_at->format('Y-m-d'),
                'is_pr' => $result->is_pr,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'workout' => [
                    'id' => $workout->id,
                    'name' => $workout->name,
                    'description' => $workout->description,
                ],
                'leaderboard' => $leaderboard,
                'total' => $leaderboard->count(),
            ],
        ]);
    }
}
