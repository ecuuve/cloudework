<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Exercise;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class ExerciseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $coach = $request->user()->coach;

        $query = Exercise::query();

        // Show public exercises + coach's own exercises
        $query->where(function($q) use ($coach) {
            $q->where('is_public', true)
              ->orWhere('created_by_coach_id', $coach?->id);
        });

        // Filters
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        if ($request->has('difficulty_level')) {
            $query->where('difficulty_level', $request->difficulty_level);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->get('per_page', 50);
        $exercises = $query->paginate($perPage);

        $formattedExercises = $exercises->map(function ($exercise) {
            return [
                'id' => $exercise->id,
                'name' => $exercise->name,
                'category' => $exercise->category,
                'description' => $exercise->description,
                'video_url' => $exercise->video_url,
                'equipment' => $exercise->equipment,
                'difficulty_level' => $exercise->difficulty_level,
                'muscle_groups' => $exercise->muscle_groups,
                'is_public' => $exercise->is_public,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'exercises' => $formattedExercises,
                'pagination' => [
                    'current_page' => $exercises->currentPage(),
                    'total_pages' => $exercises->lastPage(),
                    'total' => $exercises->total(),
                    'per_page' => $exercises->perPage(),
                ],
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $coach = $request->user()->coach;

        if (!$coach) {
            return response()->json([
                'success' => false,
                'message' => 'Only coaches can create exercises',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:200',
            'category' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'video_url' => 'nullable|url',
            'equipment' => 'nullable|array',
            'difficulty_level' => 'nullable|in:beginner,intermediate,advanced',
            'muscle_groups' => 'nullable|array',
            'is_public' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $exercise = Exercise::create([
                'name' => $request->name,
                'category' => $request->category,
                'description' => $request->description,
                'video_url' => $request->video_url,
                'equipment' => $request->equipment,
                'difficulty_level' => $request->difficulty_level ?? 'intermediate',
                'muscle_groups' => $request->muscle_groups,
                'created_by_coach_id' => $coach->id,
                'is_public' => $request->is_public ?? false,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Exercise created successfully',
                'data' => ['exercise' => $exercise],
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create exercise',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $coach = $request->user()->coach;

        $exercise = Exercise::where(function($q) use ($coach) {
            $q->where('is_public', true)
              ->orWhere('created_by_coach_id', $coach?->id);
        })->find($id);

        if (!$exercise) {
            return response()->json([
                'success' => false,
                'message' => 'Exercise not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => ['exercise' => $exercise],
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $coach = $request->user()->coach;

        if (!$coach) {
            return response()->json([
                'success' => false,
                'message' => 'Only coaches can update exercises',
            ], 403);
        }

        $exercise = Exercise::where('created_by_coach_id', $coach->id)->find($id);

        if (!$exercise) {
            return response()->json([
                'success' => false,
                'message' => 'Exercise not found or you do not have permission',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:200',
            'category' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'video_url' => 'nullable|url',
            'equipment' => 'nullable|array',
            'difficulty_level' => 'sometimes|in:beginner,intermediate,advanced',
            'muscle_groups' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $exercise->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Exercise updated successfully',
                'data' => ['exercise' => $exercise],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update exercise',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $coach = $request->user()->coach;

        if (!$coach) {
            return response()->json([
                'success' => false,
                'message' => 'Only coaches can delete exercises',
            ], 403);
        }

        $exercise = Exercise::where('created_by_coach_id', $coach->id)->find($id);

        if (!$exercise) {
            return response()->json([
                'success' => false,
                'message' => 'Exercise not found',
            ], 404);
        }

        try {
            $exercise->delete();

            return response()->json([
                'success' => true,
                'message' => 'Exercise deleted successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete exercise',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
