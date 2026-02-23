<?php
// Script para verificar un assignment específico
// Ejecutar con: php check_assignment.php 9

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$assignmentId = $argv[1] ?? null;

if (!$assignmentId) {
    echo "Uso: php check_assignment.php <assignment_id>\n";
    echo "Ejemplo: php check_assignment.php 9\n";
    exit(1);
}

echo "=== ASSIGNMENT #{$assignmentId} ===\n\n";

$assignment = DB::table('workout_assignments')->find($assignmentId);

if (!$assignment) {
    echo "Assignment no encontrado.\n";
    exit(1);
}

echo "ID: {$assignment->id}\n";
echo "Athlete ID: {$assignment->athlete_id}\n";
echo "Workout ID: {$assignment->workout_id}\n";
echo "Scheduled Date: {$assignment->scheduled_date}\n";
echo "Completed: " . ($assignment->is_completed ? 'Yes' : 'No') . "\n";
echo "\n";

echo "=== RESULTADOS DE ESTE ASSIGNMENT ===\n\n";

$results = DB::table('workout_results')
    ->where('assignment_id', $assignmentId)
    ->get();

if ($results->isEmpty()) {
    echo "No hay resultados para este assignment.\n";
} else {
    foreach ($results as $result) {
        echo "Result ID: {$result->id}\n";
        echo "Time (seconds): {$result->time_seconds}\n";
        echo "Completed at: {$result->completed_at}\n";
        echo "Section Results:\n";
        
        if ($result->section_results) {
            $sections = json_decode($result->section_results, true);
            if ($sections) {
                foreach ($sections as $idx => $section) {
                    echo "  Sección {$idx}:\n";
                    echo "    Completed: " . ($section['completed'] ? 'Yes' : 'No') . "\n";
                    echo "    Time: " . ($section['time_seconds'] ?? 0) . " seconds\n";
                    echo "    Result: " . ($section['result'] ?? 'N/A') . "\n";
                    echo "    Saved at: " . ($section['saved_at'] ?? 'N/A') . "\n";
                }
            } else {
                echo "  (JSON inválido)\n";
            }
        } else {
            echo "  (No hay section_results)\n";
        }
    }
}
