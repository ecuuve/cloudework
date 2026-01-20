<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Athlete;
use App\Models\WorkoutResult;
use App\Models\WorkoutAssignment;
use App\Models\PersonalRecord;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AnalyticsController extends Controller
{
    /**
     * Get coach dashboard statistics.
     */
    public function dashboard(Request $request): JsonResponse
    {
        $coach = $request->user()->coach;

        if (!$coach) {
            return response()->json([
                'success' => false,
                'message' => 'Only coaches can access dashboard analytics',
            ], 403);
        }

        // Date ranges
        $now = now();
        $startOfWeek = $now->copy()->startOfWeek();
        $startOfLastWeek = $now->copy()->subWeek()->startOfWeek();
        $endOfLastWeek = $now->copy()->subWeek()->endOfWeek();
        $startOfMonth = $now->copy()->startOfMonth();
        $startOfLastMonth = $now->copy()->subMonth()->startOfMonth();
        $endOfLastMonth = $now->copy()->subMonth()->endOfMonth();

        // Total Athletes
        $totalAthletes = $coach->athletes()->count();
        $activeAthletes = $coach->athletes()->where('status', 'active')->count();
        $athletesLastMonth = $coach->athletes()
            ->whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])
            ->count();
        $athletesThisMonth = $coach->athletes()
            ->where('created_at', '>=', $startOfMonth)
            ->count();
        $athletesGrowth = $athletesLastMonth > 0 
            ? round((($athletesThisMonth - $athletesLastMonth) / $athletesLastMonth) * 100, 1)
            : 0;

        // Workouts This Week
        $workoutsThisWeek = WorkoutResult::whereHas('athlete', function($q) use ($coach) {
                $q->where('coach_id', $coach->id);
            })
            ->where('completed_at', '>=', $startOfWeek)
            ->count();

        $workoutsLastWeek = WorkoutResult::whereHas('athlete', function($q) use ($coach) {
                $q->where('coach_id', $coach->id);
            })
            ->whereBetween('completed_at', [$startOfLastWeek, $endOfLastWeek])
            ->count();

        $workoutsGrowth = $workoutsLastWeek > 0
            ? round((($workoutsThisWeek - $workoutsLastWeek) / $workoutsLastWeek) * 100, 1)
            : 0;

        // Completion Rate
        $assignedThisWeek = WorkoutAssignment::where('assigned_by_coach_id', $coach->id)
            ->where('scheduled_date', '>=', $startOfWeek)
            ->where('scheduled_date', '<=', $now)
            ->count();

        $completedThisWeek = WorkoutAssignment::where('assigned_by_coach_id', $coach->id)
            ->where('scheduled_date', '>=', $startOfWeek)
            ->where('scheduled_date', '<=', $now)
            ->where('is_completed', true)
            ->count();

        $completionRate = $assignedThisWeek > 0
            ? round(($completedThisWeek / $assignedThisWeek) * 100, 1)
            : 0;

        // Average completion rate (could be historical or team average)
        $avgCompletionRate = 75; // Placeholder - calculate from historical data
        $completionGrowth = round($completionRate - $avgCompletionRate, 1);

        // PRs This Month
        $prsThisMonth = PersonalRecord::whereHas('athlete', function($q) use ($coach) {
                $q->where('coach_id', $coach->id);
            })
            ->where('achieved_at', '>=', $startOfMonth)
            ->count();

        $prsLastMonth = PersonalRecord::whereHas('athlete', function($q) use ($coach) {
                $q->where('coach_id', $coach->id);
            })
            ->whereBetween('achieved_at', [$startOfLastMonth, $endOfLastMonth])
            ->count();

        $prsGrowth = $prsLastMonth > 0
            ? round((($prsThisMonth - $prsLastMonth) / $prsLastMonth) * 100, 1)
            : 0;

        // Recent Activity
        $recentResults = WorkoutResult::with(['workout', 'athlete.user'])
            ->whereHas('athlete', function($q) use ($coach) {
                $q->where('coach_id', $coach->id);
            })
            ->orderBy('completed_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function($result) {
                return [
                    'athlete_name' => $result->athlete->user->full_name,
                    'workout_name' => $result->workout->name,
                    'completed_at' => $result->completed_at->diffForHumans(),
                    'is_pr' => $result->is_pr,
                    'rx_or_scaled' => $result->rx_or_scaled,
                ];
            });

        // Top Performers (by workouts completed this month)
        $topPerformers = Athlete::where('coach_id', $coach->id)
            ->withCount(['results as workouts_this_month' => function($q) use ($startOfMonth) {
                $q->where('completed_at', '>=', $startOfMonth);
            }])
            ->orderBy('workouts_this_month', 'desc')
            ->limit(5)
            ->get()
            ->map(function($athlete) {
                return [
                    'id' => $athlete->id,
                    'name' => $athlete->user->full_name,
                    'workouts_completed' => $athlete->workouts_this_month,
                    'current_streak' => $athlete->current_streak,
                ];
            });

        // Weekly workout distribution
        $weeklyDistribution = WorkoutResult::whereHas('athlete', function($q) use ($coach) {
                $q->where('coach_id', $coach->id);
            })
            ->where('completed_at', '>=', $startOfWeek)
            ->select(DB::raw('DATE(completed_at) as date'), DB::raw('COUNT(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->mapWithKeys(function($item) {
                return [Carbon::parse($item->date)->format('l') => $item->count];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'kpis' => [
                    'total_athletes' => [
                        'value' => $totalAthletes,
                        'active' => $activeAthletes,
                        'growth_percentage' => $athletesGrowth,
                        'trend' => $athletesGrowth >= 0 ? 'up' : 'down',
                    ],
                    'workouts_this_week' => [
                        'value' => $workoutsThisWeek,
                        'growth_percentage' => $workoutsGrowth,
                        'trend' => $workoutsGrowth >= 0 ? 'up' : 'down',
                    ],
                    'completion_rate' => [
                        'value' => $completionRate,
                        'assigned' => $assignedThisWeek,
                        'completed' => $completedThisWeek,
                        'growth_percentage' => $completionGrowth,
                        'trend' => $completionGrowth >= 0 ? 'up' : 'down',
                    ],
                    'prs_this_month' => [
                        'value' => $prsThisMonth,
                        'growth_percentage' => $prsGrowth,
                        'trend' => $prsGrowth >= 0 ? 'up' : 'down',
                    ],
                ],
                'recent_activity' => $recentResults,
                'top_performers' => $topPerformers,
                'weekly_distribution' => $weeklyDistribution,
            ],
        ]);
    }

    /**
     * Get athlete progress over time.
     */
    public function athleteProgress(Request $request, int $athleteId): JsonResponse
    {
        $coach = $request->user()->coach;

        if (!$coach) {
            return response()->json([
                'success' => false,
                'message' => 'Only coaches can access athlete progress',
            ], 403);
        }

        $athlete = $coach->athletes()->find($athleteId);

        if (!$athlete) {
            return response()->json([
                'success' => false,
                'message' => 'Athlete not found',
            ], 404);
        }

        $period = $request->get('period', '3months'); // 1month, 3months, 6months, 1year

        $startDate = match($period) {
            '1month' => now()->subMonth(),
            '3months' => now()->subMonths(3),
            '6months' => now()->subMonths(6),
            '1year' => now()->subYear(),
            default => now()->subMonths(3),
        };

        // Workouts over time
        $workoutsByWeek = WorkoutResult::where('athlete_id', $athleteId)
            ->where('completed_at', '>=', $startDate)
            ->select(
                DB::raw('YEARWEEK(completed_at) as week'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('week')
            ->orderBy('week')
            ->get()
            ->map(function($item) {
                return [
                    'week' => $item->week,
                    'workouts' => $item->count,
                ];
            });

        // PRs over time
        $prsByMonth = PersonalRecord::where('athlete_id', $athleteId)
            ->where('achieved_at', '>=', $startDate)
            ->select(
                DB::raw('DATE_FORMAT(achieved_at, "%Y-%m") as month'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->map(function($item) {
                return [
                    'month' => $item->month,
                    'prs' => $item->count,
                ];
            });

        // Completion rate by month
        $completionByMonth = DB::table('workout_assignments')
            ->where('athlete_id', $athleteId)
            ->where('scheduled_date', '>=', $startDate)
            ->select(
                DB::raw('DATE_FORMAT(scheduled_date, "%Y-%m") as month'),
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN is_completed = 1 THEN 1 ELSE 0 END) as completed')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->map(function($item) {
                $rate = $item->total > 0 ? round(($item->completed / $item->total) * 100, 1) : 0;
                return [
                    'month' => $item->month,
                    'completion_rate' => $rate,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'athlete' => [
                    'id' => $athlete->id,
                    'name' => $athlete->user->full_name,
                ],
                'period' => $period,
                'charts' => [
                    'workouts_by_week' => $workoutsByWeek,
                    'prs_by_month' => $prsByMonth,
                    'completion_rate_by_month' => $completionByMonth,
                ],
            ],
        ]);
    }

    /**
     * Get workout leaderboard for a specific workout.
     */
    public function workoutLeaderboard(Request $request, int $workoutId): JsonResponse
    {
        $coach = $request->user()->coach;

        if (!$coach) {
            return response()->json([
                'success' => false,
                'message' => 'Only coaches can access leaderboards',
            ], 403);
        }

        $rxOrScaled = $request->get('rx_or_scaled', 'rx');

        $leaderboard = WorkoutResult::with('athlete.user')
            ->whereHas('athlete', function($q) use ($coach) {
                $q->where('coach_id', $coach->id);
            })
            ->where('workout_id', $workoutId)
            ->where('rx_or_scaled', $rxOrScaled)
            ->whereNotNull('time_seconds')
            ->select('athlete_id', DB::raw('MIN(time_seconds) as best_time'))
            ->groupBy('athlete_id')
            ->orderBy('best_time', 'asc')
            ->limit(10)
            ->get()
            ->map(function($result, $index) {
                $athlete = Athlete::with('user')->find($result->athlete_id);
                $minutes = floor($result->best_time / 60);
                $seconds = $result->best_time % 60;
                
                return [
                    'rank' => $index + 1,
                    'athlete' => [
                        'id' => $athlete->id,
                        'name' => $athlete->user->full_name,
                    ],
                    'time_seconds' => $result->best_time,
                    'formatted_time' => sprintf('%d:%02d', $minutes, $seconds),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'workout_id' => $workoutId,
                'rx_or_scaled' => $rxOrScaled,
                'leaderboard' => $leaderboard,
            ],
        ]);
    }
}
