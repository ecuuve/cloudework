<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MoodLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MoodController extends Controller
{
    /**
     * Guardar un nuevo registro de mood
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if (!$user->athlete) {
            return response()->json([
                'success' => false,
                'message' => 'Only athletes can log mood',
            ], 403);
        }

        $validated = $request->validate([
            'mood_level' => 'required|integer|min:1|max:7',
            'notes' => 'nullable|string|max:500',
        ]);

        $moodLog = MoodLog::create([
            'athlete_id' => $user->athlete->id,
            'mood_level' => $validated['mood_level'],
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Mood registrado correctamente',
            'data' => [
                'mood_log' => [
                    'id' => $moodLog->id,
                    'mood_level' => $moodLog->mood_level,
                    'mood_emoji' => $moodLog->mood_emoji,
                    'mood_label' => $moodLog->mood_label,
                    'notes' => $moodLog->notes,
                    'created_at' => $moodLog->created_at->format('Y-m-d H:i:s'),
                ],
            ],
        ]);
    }

    /**
     * Obtener historial de mood del atleta
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if (!$user->athlete) {
            return response()->json([
                'success' => false,
                'message' => 'Only athletes can view mood logs',
            ], 403);
        }

        $days = $request->get('days', 30); // Últimos 30 días por defecto
        
        $moodLogs = MoodLog::where('athlete_id', $user->athlete->id)
            ->where('created_at', '>=', now()->subDays($days))
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'mood_level' => $log->mood_level,
                    'mood_emoji' => $log->mood_emoji,
                    'mood_label' => $log->mood_label,
                    'notes' => $log->notes,
                    'created_at' => $log->created_at->format('Y-m-d H:i:s'),
                    'date' => $log->created_at->format('Y-m-d'),
                    'time' => $log->created_at->format('H:i'),
                ];
            });

        // Agrupar por día para el gráfico
        $dailyAverages = MoodLog::where('athlete_id', $user->athlete->id)
            ->where('created_at', '>=', now()->subDays($days))
            ->get()
            ->groupBy(function($log) {
                return $log->created_at->format('Y-m-d');
            })
            ->map(function($dayLogs, $date) {
                return [
                    'date' => $date,
                    'average' => round($dayLogs->avg('mood_level'), 1),
                    'count' => $dayLogs->count(),
                    'min' => $dayLogs->min('mood_level'),
                    'max' => $dayLogs->max('mood_level'),
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'data' => [
                'mood_logs' => $moodLogs,
                'daily_averages' => $dailyAverages,
                'stats' => [
                    'total_logs' => $moodLogs->count(),
                    'average_mood' => round($moodLogs->avg('mood_level'), 1),
                    'days_tracked' => $dailyAverages->count(),
                ],
            ],
        ]);
    }

    /**
     * Eliminar un registro de mood
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        
        if (!$user->athlete) {
            return response()->json([
                'success' => false,
                'message' => 'Only athletes can delete mood logs',
            ], 403);
        }

        $moodLog = MoodLog::where('athlete_id', $user->athlete->id)->find($id);

        if (!$moodLog) {
            return response()->json([
                'success' => false,
                'message' => 'Mood log not found',
            ], 404);
        }

        $moodLog->delete();

        return response()->json([
            'success' => true,
            'message' => 'Mood log eliminado',
        ]);
    }
}
