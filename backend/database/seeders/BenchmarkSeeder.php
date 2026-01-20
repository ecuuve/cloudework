<?php

namespace Database\Seeders;

use App\Models\Workout;
use Illuminate\Database\Seeder;

class BenchmarkSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $benchmarks = [
            [
                'name' => 'Fran',
                'slug' => 'fran',
                'description' => '21-15-9 reps for time of Thrusters and Pull-ups',
                'workout_type' => 'benchmark',
                'benchmark_category' => 'girl',
                'difficulty_level' => 'rx',
                'is_benchmark' => true,
                'is_public' => true,
                'estimated_duration_minutes' => 10,
                'workout_structure' => [
                    'format' => 'for_time',
                    'rounds' => 3,
                    'rep_scheme' => [21, 15, 9],
                    'movements' => [
                        [
                            'name' => 'Thrusters',
                            'weight' => ['rx_male' => 95, 'rx_female' => 65, 'scaled_male' => 65, 'scaled_female' => 45],
                            'unit' => 'lbs',
                        ],
                        [
                            'name' => 'Pull-ups',
                            'modification' => 'kipping or strict',
                        ],
                    ],
                ],
                'scaling_options' => [
                    'beginner' => 'Use 35/25 lbs and ring rows instead of pull-ups',
                    'scaled' => 'Use 65/45 lbs and jumping pull-ups or band-assisted',
                ],
                'equipment_needed' => ['barbell', 'plates', 'pull-up bar'],
                'tags' => ['benchmark', 'girl', 'gymnastics', 'weightlifting', 'metcon'],
            ],
            [
                'name' => 'Helen',
                'slug' => 'helen',
                'description' => '3 rounds for time: 400m Run, 21 KB Swings, 12 Pull-ups',
                'workout_type' => 'benchmark',
                'benchmark_category' => 'girl',
                'difficulty_level' => 'rx',
                'is_benchmark' => true,
                'is_public' => true,
                'estimated_duration_minutes' => 12,
                'workout_structure' => [
                    'format' => 'for_time',
                    'rounds' => 3,
                    'movements' => [
                        [
                            'name' => '400m Run',
                            'distance' => 400,
                            'unit' => 'meters',
                        ],
                        [
                            'name' => 'Kettlebell Swings',
                            'reps' => 21,
                            'weight' => ['rx_male' => 53, 'rx_female' => 35, 'scaled_male' => 35, 'scaled_female' => 26],
                            'unit' => 'lbs',
                        ],
                        [
                            'name' => 'Pull-ups',
                            'reps' => 12,
                        ],
                    ],
                ],
                'scaling_options' => [
                    'beginner' => 'Run 200m, use 26/18 lbs KB, do ring rows',
                    'scaled' => 'Same distances, lighter KB, jumping pull-ups',
                ],
                'equipment_needed' => ['kettlebell', 'pull-up bar', 'running track'],
                'tags' => ['benchmark', 'girl', 'running', 'gymnastics', 'weightlifting'],
            ],
            [
                'name' => 'Cindy',
                'slug' => 'cindy',
                'description' => 'AMRAP 20 minutes: 5 Pull-ups, 10 Push-ups, 15 Air Squats',
                'workout_type' => 'benchmark',
                'benchmark_category' => 'girl',
                'difficulty_level' => 'intermediate',
                'is_benchmark' => true,
                'is_public' => true,
                'estimated_duration_minutes' => 20,
                'workout_structure' => [
                    'format' => 'amrap',
                    'time_cap' => 20,
                    'movements' => [
                        [
                            'name' => 'Pull-ups',
                            'reps' => 5,
                        ],
                        [
                            'name' => 'Push-ups',
                            'reps' => 10,
                        ],
                        [
                            'name' => 'Air Squats',
                            'reps' => 15,
                        ],
                    ],
                ],
                'scaling_options' => [
                    'beginner' => 'Ring rows, knee push-ups, assisted squats',
                    'scaled' => 'Jumping pull-ups, hand-release push-ups',
                ],
                'equipment_needed' => ['pull-up bar'],
                'tags' => ['benchmark', 'girl', 'bodyweight', 'gymnastics', 'amrap'],
            ],
            [
                'name' => 'Murph',
                'slug' => 'murph',
                'description' => 'For time: 1 mile Run, 100 Pull-ups, 200 Push-ups, 300 Squats, 1 mile Run (20lb vest)',
                'workout_type' => 'benchmark',
                'benchmark_category' => 'hero',
                'difficulty_level' => 'advanced',
                'is_benchmark' => true,
                'is_public' => true,
                'estimated_duration_minutes' => 45,
                'workout_structure' => [
                    'format' => 'for_time',
                    'movements' => [
                        [
                            'name' => '1 Mile Run',
                            'distance' => 1,
                            'unit' => 'mile',
                        ],
                        [
                            'name' => 'Pull-ups',
                            'reps' => 100,
                            'note' => 'Partition as needed',
                        ],
                        [
                            'name' => 'Push-ups',
                            'reps' => 200,
                            'note' => 'Partition as needed',
                        ],
                        [
                            'name' => 'Air Squats',
                            'reps' => 300,
                            'note' => 'Partition as needed',
                        ],
                        [
                            'name' => '1 Mile Run',
                            'distance' => 1,
                            'unit' => 'mile',
                        ],
                    ],
                    'note' => 'Wear 20lb vest if possible. Partition pull-ups, push-ups, and squats as needed.',
                ],
                'scaling_options' => [
                    'beginner' => 'No vest, half reps (50-100-150), 800m runs',
                    'scaled' => 'No vest, full reps, partition heavily',
                    'rx_plus' => 'With 20lb vest, no partitioning (Murph style)',
                ],
                'equipment_needed' => ['pull-up bar', 'running track', 'weight vest (optional)'],
                'tags' => ['benchmark', 'hero', 'memorial day', 'chipper', 'long'],
            ],
            [
                'name' => 'Grace',
                'slug' => 'grace',
                'description' => '30 Clean and Jerks for time',
                'workout_type' => 'benchmark',
                'benchmark_category' => 'girl',
                'difficulty_level' => 'rx',
                'is_benchmark' => true,
                'is_public' => true,
                'estimated_duration_minutes' => 5,
                'workout_structure' => [
                    'format' => 'for_time',
                    'movements' => [
                        [
                            'name' => 'Clean and Jerk',
                            'reps' => 30,
                            'weight' => ['rx_male' => 135, 'rx_female' => 95, 'scaled_male' => 95, 'scaled_female' => 65],
                            'unit' => 'lbs',
                        ],
                    ],
                ],
                'scaling_options' => [
                    'beginner' => 'Use 45/35 lbs and focus on form',
                    'scaled' => 'Use 95/65 lbs',
                ],
                'equipment_needed' => ['barbell', 'plates'],
                'tags' => ['benchmark', 'girl', 'weightlifting', 'olympic lifting'],
            ],
            [
                'name' => 'Karen',
                'slug' => 'karen',
                'description' => '150 Wall Balls for time',
                'workout_type' => 'benchmark',
                'benchmark_category' => 'girl',
                'difficulty_level' => 'rx',
                'is_benchmark' => true,
                'is_public' => true,
                'estimated_duration_minutes' => 12,
                'workout_structure' => [
                    'format' => 'for_time',
                    'movements' => [
                        [
                            'name' => 'Wall Balls',
                            'reps' => 150,
                            'weight' => ['rx_male' => 20, 'rx_female' => 14],
                            'height' => ['rx_male' => 10, 'rx_female' => 9],
                            'unit_weight' => 'lbs',
                            'unit_height' => 'feet',
                        ],
                    ],
                ],
                'scaling_options' => [
                    'beginner' => '10/8 lbs to 9/8 feet',
                    'scaled' => '14/10 lbs to 9/8 feet',
                ],
                'equipment_needed' => ['medicine ball', 'wall target'],
                'tags' => ['benchmark', 'girl', 'monostructural', 'legs'],
            ],
        ];

        foreach ($benchmarks as $benchmark) {
            Workout::create($benchmark);
        }

        $this->command->info('Benchmark workouts seeded successfully!');
    }
}
