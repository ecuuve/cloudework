<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Coach;
use App\Models\Athlete;
use App\Models\Workout;
use App\Models\WorkoutAssignment;
use App\Models\WorkoutResult;
use App\Models\PersonalRecord;
use App\Models\AthleteGroup;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Create Demo Coach
        $coachUser = User::create([
            'email' => 'demo@cloudework.com',
            'password' => Hash::make('demo123'),
            'role' => 'coach',
            'first_name' => 'Juan',
            'last_name' => 'Pérez',
            'phone' => '+506-8888-8888',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $coach = Coach::create([
            'user_id' => $coachUser->id,
            'certification_level' => 'CF-L2',
            'years_experience' => 5,
            'bio' => 'Coach certificado con 5 años de experiencia en CrossFit',
            'specialties' => ['Olympic Lifting', 'Gymnastics', 'Endurance'],
            'subscription_status' => 'active',
            'subscription_plan' => 'pro',
            'subscription_start_date' => now()->subMonths(6),
            'subscription_end_date' => now()->addMonths(6),
            'max_athletes' => 50,
        ]);

        $this->command->info('✅ Demo Coach created: demo@cloudework.com / demo123');

        // 2. Create Demo Athletes
        $athletes = [];
        $athletesData = [
            ['María', 'González', 'maria@example.com', 'female', 28],
            ['Carlos', 'Rodríguez', 'carlos@example.com', 'male', 32],
            ['Laura', 'Pérez', 'laura@example.com', 'female', 25],
            ['Juan', 'Sánchez', 'juan@example.com', 'male', 30],
            ['Ana', 'Martínez', 'ana@example.com', 'female', 27],
            ['Pedro', 'López', 'pedro@example.com', 'male', 35],
            ['Sofia', 'García', 'sofia@example.com', 'female', 24],
            ['Diego', 'Fernández', 'diego@example.com', 'male', 29],
        ];

        foreach ($athletesData as $index => $data) {
            $athleteUser = User::create([
                'email' => $data[2],
                'password' => Hash::make('password123'),
                'role' => 'athlete',
                'first_name' => $data[0],
                'last_name' => $data[1],
                'phone' => '+506-' . rand(1000, 9999) . '-' . rand(1000, 9999),
                'is_active' => true,
                'email_verified_at' => now(),
            ]);

            $athlete = Athlete::create([
                'user_id' => $athleteUser->id,
                'coach_id' => $coach->id,
                'date_of_birth' => now()->subYears($data[4])->subMonths(rand(1, 11)),
                'gender' => $data[3],
                'height_cm' => $data[3] === 'male' ? rand(170, 190) : rand(155, 175),
                'weight_kg' => $data[3] === 'male' ? rand(70, 95) : rand(55, 75),
                'goals' => 'Mejorar fuerza y resistencia general',
                'start_date' => now()->subMonths(rand(1, 12)),
                'status' => $index < 6 ? 'active' : ($index === 6 ? 'on_hold' : 'active'),
            ]);

            $athletes[] = $athlete;
        }

        $this->command->info('✅ ' . count($athletes) . ' Demo Athletes created');

        // 3. Create Demo Groups
        $group1 = AthleteGroup::create([
            'coach_id' => $coach->id,
            'name' => 'Principiantes',
            'description' => 'Atletas nuevos en CrossFit',
            'color' => '#4CAF50',
        ]);

        $group2 = AthleteGroup::create([
            'coach_id' => $coach->id,
            'name' => 'Avanzados',
            'description' => 'Atletas con experiencia',
            'color' => '#FF6B35',
        ]);

        // Assign athletes to groups
        $group1->athletes()->attach([$athletes[2]->id, $athletes[4]->id, $athletes[6]->id]);
        $group2->athletes()->attach([$athletes[0]->id, $athletes[1]->id, $athletes[3]->id]);

        $this->command->info('✅ 2 Demo Groups created');

        // 4. Create Assignments for this week
        $workouts = Workout::where('is_benchmark', true)->limit(10)->get();
        $startOfWeek = now()->startOfWeek();

        foreach ($athletes as $athleteIndex => $athlete) {
            if ($athlete->status !== 'active') continue;

            for ($day = 0; $day < 5; $day++) {
                $scheduledDate = $startOfWeek->copy()->addDays($day);
                $workout = $workouts->random();
                $isCompleted = $scheduledDate->isPast() ? (rand(1, 100) > 10) : false;

                $assignment = WorkoutAssignment::create([
                    'workout_id' => $workout->id,
                    'assigned_by_coach_id' => $coach->id,
                    'athlete_id' => $athlete->id,
                    'scheduled_date' => $scheduledDate,
                    'notes' => $day === 0 ? 'Focus on form!' : null,
                    'is_completed' => $isCompleted,
                    'priority' => ['low', 'medium', 'high'][rand(0, 2)],
                ]);

                // Create results for completed assignments
                if ($isCompleted) {
                    $this->createResult($assignment, $athlete, $workout);
                }
            }
        }

        $this->command->info('✅ Workout Assignments created for current week');

        // 5. Create some historical results (last 3 months)
        foreach ($athletes as $athlete) {
            if ($athlete->status !== 'active') continue;

            for ($i = 0; $i < 20; $i++) {
                $workout = $workouts->random();
                $completedAt = now()->subDays(rand(7, 90));

                // Create assignment
                $assignment = WorkoutAssignment::create([
                    'workout_id' => $workout->id,
                    'assigned_by_coach_id' => $coach->id,
                    'athlete_id' => $athlete->id,
                    'scheduled_date' => $completedAt->copy()->subDay(),
                    'is_completed' => true,
                ]);

                // Create result
                $this->createResult($assignment, $athlete, $workout, $completedAt);
            }
        }

        $this->command->info('✅ Historical results created');

        $this->command->info('');
        $this->command->info('🎉 Demo data seeded successfully!');
        $this->command->info('');
        $this->command->info('📧 Coach Login: demo@cloudework.com');
        $this->command->info('🔑 Password: demo123');
        $this->command->info('');
    }

    /**
     * Create a workout result with realistic data.
     */
    private function createResult($assignment, $athlete, $workout, $completedAt = null)
    {
        $completedAt = $completedAt ?? now();
        
        // Determine if RX or Scaled (athletes get better over time)
        $daysSinceStart = $athlete->start_date->diffInDays(now());
        $rxProbability = min(90, 30 + ($daysSinceStart / 3)); // Starts at 30%, increases over time
        $rxOrScaled = rand(1, 100) <= $rxProbability ? 'rx' : 'scaled';

        // Generate realistic time based on workout
        $baseTime = 180; // 3 minutes base
        if (str_contains(strtolower($workout->name), 'murph')) {
            $baseTime = 2400; // 40 minutes for Murph
        } elseif (str_contains(strtolower($workout->name), 'fran')) {
            $baseTime = 300; // 5 minutes for Fran
        } elseif (str_contains(strtolower($workout->name), 'helen')) {
            $baseTime = 600; // 10 minutes for Helen
        } elseif (str_contains(strtolower($workout->name), 'cindy')) {
            $baseTime = 1200; // 20 minutes (AMRAP)
        }

        // Add variation
        $timeSeconds = $baseTime + rand(-60, 120);
        
        // Scaled is generally slower
        if ($rxOrScaled === 'scaled') {
            $timeSeconds = (int)($timeSeconds * 1.15);
        }

        // Check for PR
        $isPR = false;
        $previousBest = WorkoutResult::where('athlete_id', $athlete->id)
            ->where('workout_id', $workout->id)
            ->where('rx_or_scaled', 'rx')
            ->whereNotNull('time_seconds')
            ->min('time_seconds');

        if ($rxOrScaled === 'rx' && (!$previousBest || $timeSeconds < $previousBest)) {
            $isPR = true;
        }

        $result = WorkoutResult::create([
            'assignment_id' => $assignment->id,
            'athlete_id' => $athlete->id,
            'workout_id' => $workout->id,
            'completed_at' => $completedAt,
            'time_seconds' => $timeSeconds,
            'rx_or_scaled' => $rxOrScaled,
            'feeling_rating' => rand(3, 5),
            'notes' => $isPR ? '🎉 New PR!' : null,
            'is_pr' => $isPR,
        ]);

        // Create PR record if applicable
        if ($isPR) {
            PersonalRecord::create([
                'athlete_id' => $athlete->id,
                'movement_name' => $workout->name,
                'record_type' => 'time',
                'value' => $timeSeconds,
                'unit' => 'seconds',
                'workout_result_id' => $result->id,
                'achieved_at' => $completedAt,
            ]);
        }

        return $result;
    }
}
