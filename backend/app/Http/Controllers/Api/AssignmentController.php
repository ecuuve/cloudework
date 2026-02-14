<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WorkoutAssignment;
use App\Models\Athlete;
use App\Models\Workout;
use App\Models\AthleteGroup;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class AssignmentController extends Controller
{
    /**
     * Display a listing of assignments.
     */
    public function index(Request $request): JsonResponse
    {
        $coach = $request->user()->coach;

        if (!$coach) {
            return response()->json([
                'success' => false,
                'message' => 'Only coaches can access assignments',
            ], 403);
        }

        $query = WorkoutAssignment::with(['workout', 'athlete.user', 'group'])
            ->where('assigned_by_coach_id', $coach->id);

        // Filters
        if ($request->has('athlete_id')) {
            $query->where('athlete_id', $request->athlete_id);
        }

        if ($request->has('group_id')) {
            $query->where('group_id', $request->group_id);
        }

        if ($request->has('is_completed')) {
            $query->where('is_completed', filter_var($request->is_completed, FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->has('scheduled_date')) {
            $query->whereDate('scheduled_date', $request->scheduled_date);
        }

        if ($request->has('date_from') && $request->has('date_to')) {
            $query->whereBetween('scheduled_date', [$request->date_from, $request->date_to]);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'scheduled_date');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->get('per_page', 20);
        $assignments = $query->paginate($perPage);

        // Format response
        $formattedAssignments = $assignments->map(function ($assignment) {
            $data = [
                'id' => $assignment->id,
                'workout' => [
                    'id' => $assignment->workout->id,
                    'name' => $assignment->workout->name,
                    'type' => $assignment->workout->workout_type,
                    'difficulty' => $assignment->workout->difficulty_level,
                ],
                'scheduled_date' => $assignment->scheduled_date->format('Y-m-d'),
                'is_completed' => $assignment->is_completed,
                'priority' => $assignment->priority,
                'notes' => $assignment->notes,
                'created_at' => $assignment->created_at->format('Y-m-d H:i'),
            ];

            if ($assignment->athlete_id) {
                $data['athlete'] = [
                    'id' => $assignment->athlete->id,
                    'name' => $assignment->athlete->user->full_name,
                ];
            }

            if ($assignment->group_id) {
                $data['group'] = [
                    'id' => $assignment->group->id,
                    'name' => $assignment->group->name,
                ];
            }

            return $data;
        });

        return response()->json([
            'success' => true,
            'data' => [
                'assignments' => $formattedAssignments,
                'pagination' => [
                    'current_page' => $assignments->currentPage(),
                    'total_pages' => $assignments->lastPage(),
                    'total' => $assignments->total(),
                    'per_page' => $assignments->perPage(),
                ],
            ],
        ]);
    }

    /**
     * Assign workout to individual athlete.
     */
    public function store(Request $request): JsonResponse
    {
        $coach = $request->user()->coach;

        if (!$coach) {
            return response()->json([
                'success' => false,
                'message' => 'Only coaches can create assignments',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'workout_id' => 'required|exists:workouts,id',
            'athlete_id' => 'required_without:group_id|exists:athletes,id',
            'group_id' => 'required_without:athlete_id|exists:athlete_groups,id',
            'scheduled_date' => 'required|date',
            'notes' => 'nullable|string',
            'priority' => 'nullable|in:low,medium,high',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Verify workout exists
        $workout = Workout::find($request->workout_id);
        if (!$workout) {
            return response()->json([
                'success' => false,
                'message' => 'Workout not found',
            ], 404);
        }

        // Verify athlete or group belongs to coach
        if ($request->has('athlete_id')) {
            $athlete = Athlete::where('id', $request->athlete_id)
                ->where('coach_id', $coach->id)
                ->first();
            
            if (!$athlete) {
                return response()->json([
                    'success' => false,
                    'message' => 'Athlete not found or does not belong to you',
                ], 404);
            }
        }

        if ($request->has('group_id')) {
            $group = AthleteGroup::where('id', $request->group_id)
                ->where('coach_id', $coach->id)
                ->first();
            
            if (!$group) {
                return response()->json([
                    'success' => false,
                    'message' => 'Group not found or does not belong to you',
                ], 404);
            }
        }

        try {
            $assignment = WorkoutAssignment::create([
                'workout_id' => $request->workout_id,
                'assigned_by_coach_id' => $coach->id,
                'athlete_id' => $request->athlete_id,
                'group_id' => $request->group_id,
                'scheduled_date' => $request->scheduled_date,
                'notes' => $request->notes,
                'priority' => $request->priority ?? 'medium',
                'is_completed' => false,
            ]);

            $assignment->load('workout');

            return response()->json([
                'success' => true,
                'message' => 'Workout assigned successfully',
                'data' => [
                    'assignment' => [
                        'id' => $assignment->id,
                        'workout_name' => $assignment->workout->name,
                        'scheduled_date' => $assignment->scheduled_date->format('Y-m-d'),
                    ],
                ],
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create assignment',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk assign workout to multiple athletes.
     */
    public function bulkAssign(Request $request): JsonResponse
    {
        $coach = $request->user()->coach;

        if (!$coach) {
            return response()->json([
                'success' => false,
                'message' => 'Only coaches can create assignments',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'workout_id' => 'required|exists:workouts,id',
            'athlete_ids' => 'required|array|min:1',
            'athlete_ids.*' => 'exists:athletes,id',
            'scheduled_date' => 'required|date',
            'notes' => 'nullable|string',
            'priority' => 'nullable|in:low,medium,high',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Verify all athletes belong to coach
        $athletes = Athlete::whereIn('id', $request->athlete_ids)
            ->where('coach_id', $coach->id)
            ->pluck('id');

        if ($athletes->count() !== count($request->athlete_ids)) {
            return response()->json([
                'success' => false,
                'message' => 'Some athletes do not belong to you',
            ], 403);
        }

        try {
            DB::beginTransaction();

            $assignments = [];
            foreach ($request->athlete_ids as $athleteId) {
                $assignment = WorkoutAssignment::create([
                    'workout_id' => $request->workout_id,
                    'assigned_by_coach_id' => $coach->id,
                    'athlete_id' => $athleteId,
                    'scheduled_date' => $request->scheduled_date,
                    'notes' => $request->notes,
                    'priority' => $request->priority ?? 'medium',
                    'is_completed' => false,
                ]);
                
                $assignments[] = $assignment->id;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Workout assigned to ' . count($assignments) . ' athletes',
                'data' => [
                    'assignments_created' => count($assignments),
                    'assignment_ids' => $assignments,
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create bulk assignments',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get calendar view of assignments.
     */
    public function calendar(Request $request): JsonResponse
    {
        $coach = $request->user()->coach;

        if (!$coach) {
            return response()->json([
                'success' => false,
                'message' => 'Only coaches can access calendar',
            ], 403);
        }

        $startDate = $request->get('start_date', now()->startOfWeek()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->endOfWeek()->format('Y-m-d'));

        $assignments = WorkoutAssignment::with(['workout', 'athlete.user'])
            ->where('assigned_by_coach_id', $coach->id)
            ->whereBetween('scheduled_date', [$startDate, $endDate])
            ->get()
            ->groupBy(function($item) {
                return $item->scheduled_date->format('Y-m-d');
            })
            ->map(function($dayAssignments, $date) {
                return [
                    'date' => $date,
                    'total_assignments' => $dayAssignments->count(),
                    'completed' => $dayAssignments->where('is_completed', true)->count(),
                    'pending' => $dayAssignments->where('is_completed', false)->count(),
                    'assignments' => $dayAssignments->map(function($assignment) {
                        return [
                            'id' => $assignment->id,
                            'workout_name' => $assignment->workout->name,
                            'athlete_name' => $assignment->athlete ? $assignment->athlete->user->full_name : 'Group',
                            'is_completed' => $assignment->is_completed,
                            'priority' => $assignment->priority,
                        ];
                    })->values(),
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'data' => [
                'calendar' => $assignments,
                'date_range' => [
                    'start' => $startDate,
                    'end' => $endDate,
                ],
            ],
        ]);
    }

    /**
     * Update assignment.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $coach = $request->user()->coach;

        if (!$coach) {
            return response()->json([
                'success' => false,
                'message' => 'Only coaches can update assignments',
            ], 403);
        }

        $assignment = WorkoutAssignment::where('assigned_by_coach_id', $coach->id)
            ->find($id);

        if (!$assignment) {
            return response()->json([
                'success' => false,
                'message' => 'Assignment not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'scheduled_date' => 'sometimes|date',
            'notes' => 'nullable|string',
            'priority' => 'sometimes|in:low,medium,high',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $assignment->update($request->only(['scheduled_date', 'notes', 'priority']));

            return response()->json([
                'success' => true,
                'message' => 'Assignment updated successfully',
                'data' => [
                    'assignment' => [
                        'id' => $assignment->id,
                        'scheduled_date' => $assignment->scheduled_date->format('Y-m-d'),
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update assignment',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete assignment.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $coach = $request->user()->coach;

        if (!$coach) {
            return response()->json([
                'success' => false,
                'message' => 'Only coaches can delete assignments',
            ], 403);
        }

        $assignment = WorkoutAssignment::where('assigned_by_coach_id', $coach->id)
            ->find($id);

        if (!$assignment) {
            return response()->json([
                'success' => false,
                'message' => 'Assignment not found..',
            ], 404);
        }

        // Don't allow deleting completed assignments with results
        if ($assignment->is_completed && $assignment->result) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete completed assignment with recorded result',
            ], 422);
        }

        try {
            $assignment->delete();

            return response()->json([
                'success' => true,
                'message' => 'Assignment deleted successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete assignment',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified assignment.
     */
    public function show(Request $request, $id): JsonResponse
    {
        $coach = $request->user()->coach;

        if (!$coach) {
            return response()->json([
                'success' => false,
                'message' => 'Only coaches can view assignments',
            ], 403);
        }

        $assignment = WorkoutAssignment::with(['workout', 'athlete.user', 'result'])
            ->where('assigned_by_coach_id', $coach->id)
            ->find($id);

        if (!$assignment) {
            return response()->json([
                'success' => false,
                'message' => 'Assignment not found...',
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
                    'athlete' => $assignment->athlete ? [
                        'id' => $assignment->athlete->id,
                        'name' => $assignment->athlete->user->full_name,
                        'email' => $assignment->athlete->user->email,
                    ] : null,
                    'scheduled_date' => $assignment->scheduled_date->format('Y-m-d'),
                    'is_completed' => $assignment->is_completed,
                    'priority' => $assignment->priority,
                    'notes' => $assignment->notes,
                    'result' => $assignment->result ? [
                        'time_seconds' => $assignment->result->time_seconds,
                        'rounds_completed' => $assignment->result->rounds_completed,
                        'rx_or_scaled' => $assignment->result->rx_or_scaled,
                        'feeling_rating' => $assignment->result->feeling_rating,
                        'is_pr' => $assignment->result->is_pr,
                    ] : null,
                ],
            ],
        ]);
    }

}
