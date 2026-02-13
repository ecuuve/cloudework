<?php
/**
 * Script de diagnóstico para verificar assignments en la base de datos
 * 
 * Uso desde backend/:
 *   php check-assignments.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Athlete;
use App\Models\WorkoutAssignment;
use App\Models\Workout;

echo "\n";
echo "═══════════════════════════════════════════════════════\n";
echo "  DIAGNÓSTICO DE ASSIGNMENTS\n";
echo "═══════════════════════════════════════════════════════\n";
echo "\n";

// 1. Ver todos los usuarios
echo "📊 USUARIOS EN EL SISTEMA:\n";
echo "──────────────────────────────────────────────────────\n";
$users = User::all();
foreach ($users as $user) {
    echo sprintf("  %-20s | %-30s | %s\n", 
        $user->role, 
        $user->email, 
        $user->first_name . ' ' . $user->last_name
    );
}
echo "\n";

// 2. Ver todos los atletas
echo "🏋️  ATLETAS:\n";
echo "──────────────────────────────────────────────────────\n";
$athletes = Athlete::with('user')->get();
if ($athletes->isEmpty()) {
    echo "  ❌ NO HAY ATLETAS\n";
} else {
    foreach ($athletes as $athlete) {
        echo sprintf("  ID: %-3d | User ID: %-3d | %s | Coach ID: %s\n",
            $athlete->id,
            $athlete->user_id,
            $athlete->user->email,
            $athlete->coach_id ?? 'NULL'
        );
    }
}
echo "\n";

// 3. Ver todos los workouts
echo "💪 WORKOUTS CREADOS:\n";
echo "──────────────────────────────────────────────────────\n";
$workouts = Workout::all();
if ($workouts->isEmpty()) {
    echo "  ❌ NO HAY WORKOUTS\n";
} else {
    foreach ($workouts as $workout) {
        echo sprintf("  ID: %-3d | %s\n", $workout->id, $workout->name);
    }
}
echo "\n";

// 4. Ver TODOS los assignments
echo "📋 ASSIGNMENTS (TODOS):\n";
echo "──────────────────────────────────────────────────────\n";
$assignments = WorkoutAssignment::with(['workout', 'athlete.user'])->get();
if ($assignments->isEmpty()) {
    echo "  ❌ NO HAY ASSIGNMENTS\n";
    echo "\n";
    echo "  💡 SOLUCIÓN: El coach debe:\n";
    echo "     1. Ir a Programación (calendar.html)\n";
    echo "     2. Crear/seleccionar un workout\n";
    echo "     3. Asignarlo al atleta con fecha de hoy\n";
} else {
    foreach ($assignments as $assignment) {
        $athleteName = $assignment->athlete 
            ? ($assignment->athlete->user->email ?? 'Usuario sin email')
            : '❌ athlete_id NULL';
        
        echo sprintf("  ID: %-3d | Workout: %-25s | Atleta: %-30s | Fecha: %s | Completado: %s\n",
            $assignment->id,
            $assignment->workout->name ?? 'SIN WORKOUT',
            $athleteName,
            $assignment->scheduled_date->format('Y-m-d'),
            $assignment->is_completed ? 'Sí' : 'No'
        );
    }
}
echo "\n";

// 5. Ver assignments de HOY específicamente
$today = now()->format('Y-m-d');
echo "📅 ASSIGNMENTS DE HOY ($today):\n";
echo "──────────────────────────────────────────────────────\n";
$todayAssignments = WorkoutAssignment::with(['workout', 'athlete.user'])
    ->whereDate('scheduled_date', $today)
    ->get();

if ($todayAssignments->isEmpty()) {
    echo "  ⚠️  NO HAY ASSIGNMENTS PARA HOY\n";
    echo "\n";
    echo "  💡 Para que aparezcan en el dashboard del atleta:\n";
    echo "     - El assignment debe tener scheduled_date = $today\n";
    echo "     - El assignment debe tener athlete_id válido\n";
} else {
    foreach ($todayAssignments as $assignment) {
        echo sprintf("  ✅ ID %d | %s | Atleta: %s\n",
            $assignment->id,
            $assignment->workout->name,
            $assignment->athlete->user->email ?? 'NULL'
        );
    }
}
echo "\n";

// 6. Verificar el atleta de prueba específicamente
echo "🔍 VERIFICACIÓN DEL ATLETA atleta@coaching.com:\n";
echo "──────────────────────────────────────────────────────\n";
$testAthlete = Athlete::whereHas('user', function($q) {
    $q->where('email', 'atleta@coaching.com');
})->first();

if (!$testAthlete) {
    echo "  ❌ NO EXISTE el atleta atleta@coaching.com\n";
    echo "  💡 Ejecuta: php artisan db:seed --class=EnsureAdminSeeder\n";
} else {
    echo sprintf("  ✅ Atleta encontrado (ID: %d, User ID: %d)\n", 
        $testAthlete->id, 
        $testAthlete->user_id
    );
    
    $athleteAssignments = WorkoutAssignment::where('athlete_id', $testAthlete->id)
        ->with('workout')
        ->get();
    
    if ($athleteAssignments->isEmpty()) {
        echo "  ⚠️  Este atleta NO TIENE ASSIGNMENTS\n";
        echo "  💡 El coach debe asignarle un workout desde calendar.html\n";
    } else {
        echo sprintf("  ✅ Tiene %d assignment(s):\n", $athleteAssignments->count());
        foreach ($athleteAssignments as $a) {
            echo sprintf("     - %s (fecha: %s)\n", 
                $a->workout->name,
                $a->scheduled_date->format('Y-m-d')
            );
        }
    }
}

echo "\n";
echo "═══════════════════════════════════════════════════════\n";
echo "\n";
