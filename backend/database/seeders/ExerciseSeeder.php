<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Exercise;

class ExerciseSeeder extends Seeder
{
    public function run(): void
    {
        $exercises = [
            // Gymnastics
            ['name' => 'Pull-ups', 'category' => 'gymnastics', 'equipment' => ['pull-up bar'], 'muscle_groups' => ['back', 'arms']],
            ['name' => 'Push-ups', 'category' => 'gymnastics', 'equipment' => [], 'muscle_groups' => ['chest', 'arms']],
            ['name' => 'Handstand Push-ups', 'category' => 'gymnastics', 'equipment' => ['wall'], 'muscle_groups' => ['shoulders', 'arms']],
            ['name' => 'Muscle-ups', 'category' => 'gymnastics', 'equipment' => ['rings', 'pull-up bar'], 'muscle_groups' => ['back', 'arms', 'chest']],
            ['name' => 'Toes to Bar', 'category' => 'gymnastics', 'equipment' => ['pull-up bar'], 'muscle_groups' => ['core']],
            
            // Weightlifting
            ['name' => 'Back Squat', 'category' => 'strength', 'equipment' => ['barbell', 'rack'], 'muscle_groups' => ['legs']],
            ['name' => 'Front Squat', 'category' => 'strength', 'equipment' => ['barbell', 'rack'], 'muscle_groups' => ['legs', 'core']],
            ['name' => 'Deadlift', 'category' => 'strength', 'equipment' => ['barbell'], 'muscle_groups' => ['back', 'legs']],
            ['name' => 'Bench Press', 'category' => 'strength', 'equipment' => ['barbell', 'bench'], 'muscle_groups' => ['chest', 'arms']],
            ['name' => 'Overhead Press', 'category' => 'strength', 'equipment' => ['barbell'], 'muscle_groups' => ['shoulders', 'arms']],
            
            // Olympic Lifts
            ['name' => 'Clean and Jerk', 'category' => 'weightlifting', 'equipment' => ['barbell'], 'muscle_groups' => ['full body']],
            ['name' => 'Snatch', 'category' => 'weightlifting', 'equipment' => ['barbell'], 'muscle_groups' => ['full body']],
            ['name' => 'Clean', 'category' => 'weightlifting', 'equipment' => ['barbell'], 'muscle_groups' => ['legs', 'back']],
            
            // MetCon
            ['name' => 'Thrusters', 'category' => 'metcon', 'equipment' => ['barbell', 'dumbbells'], 'muscle_groups' => ['legs', 'shoulders']],
            ['name' => 'Burpees', 'category' => 'metcon', 'equipment' => [], 'muscle_groups' => ['full body']],
            ['name' => 'Wall Balls', 'category' => 'metcon', 'equipment' => ['medicine ball', 'wall'], 'muscle_groups' => ['legs', 'shoulders']],
            ['name' => 'Box Jumps', 'category' => 'metcon', 'equipment' => ['box'], 'muscle_groups' => ['legs']],
            ['name' => 'Rowing', 'category' => 'cardio', 'equipment' => ['rowing machine'], 'muscle_groups' => ['full body']],
            ['name' => 'Assault Bike', 'category' => 'cardio', 'equipment' => ['assault bike'], 'muscle_groups' => ['full body']],
            ['name' => 'Running', 'category' => 'cardio', 'equipment' => [], 'muscle_groups' => ['legs']],
            
            // Mobility
            ['name' => 'Stretching', 'category' => 'mobility', 'equipment' => [], 'muscle_groups' => ['full body']],
            ['name' => 'Foam Rolling', 'category' => 'mobility', 'equipment' => ['foam roller'], 'muscle_groups' => ['full body']],
        ];

        foreach ($exercises as $exercise) {
            Exercise::create(array_merge($exercise, [
                'is_public' => true,
                'difficulty_level' => 'intermediate',
                'description' => 'Ejercicio básico de ' . $exercise['category'],
            ]));
        }
    }
}
