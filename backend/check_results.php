<?php
// Script para verificar qué hay en workout_results
// Ejecutar con: php check_results.php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== WORKOUT RESULTS ===\n\n";

$results = DB::table('workout_results')
    ->orderBy('id', 'desc')
    ->limit(5)
    ->get();

if ($results->isEmpty()) {
    echo "No hay resultados en la tabla.\n";
} else {
    foreach ($results as $result) {
        echo "ID: {$result->id}\n";
        echo "Assignment ID: {$result->assignment_id}\n";
        echo "Athlete ID: {$result->athlete_id}\n";
        echo "Workout ID: {$result->workout_id}\n";
        echo "Time (seconds): {$result->time_seconds}\n";
        echo "Completed at: {$result->completed_at}\n";
        echo "Section Results: " . ($result->section_results ?? 'NULL') . "\n";
        echo "---\n";
    }
}

echo "\n=== ÚLTIMAS SECCIONES GUARDADAS ===\n\n";

$resultsWithSections = DB::table('workout_results')
    ->whereNotNull('section_results')
    ->orderBy('updated_at', 'desc')
    ->limit(3)
    ->get();

foreach ($resultsWithSections as $result) {
    echo "Result ID: {$result->id}\n";
    echo "Updated at: {$result->updated_at}\n";
    
    if ($result->section_results) {
        $sections = json_decode($result->section_results, true);
        if ($sections) {
            foreach ($sections as $idx => $section) {
                echo "  Sección {$idx}:\n";
                echo "    Completed: " . ($section['completed'] ? 'Yes' : 'No') . "\n";
                echo "    Time: " . ($section['time_seconds'] ?? 0) . " seconds\n";
                echo "    Result: " . ($section['result'] ?? 'N/A') . "\n";
            }
        }
    }
    echo "---\n";
}
