<?php
/**
 * Script para probar los endpoints que usan los calendarios
 */
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\WorkoutAssignment;
use Illuminate\Support\Facades\Route;

echo "\n═══════════════════════════════════════════\n";
echo "  TEST DE ENDPOINTS\n";
echo "═══════════════════════════════════════════\n\n";

// 1. Verificar que las rutas existen
echo "📍 RUTAS REGISTRADAS:\n";
$routes = Route::getRoutes();
$relevantRoutes = [
    'api/v1/my/assignments',
    'api/v1/assignments/calendar',
];

foreach ($relevantRoutes as $path) {
    $found = false;
    foreach ($routes as $route) {
        if (str_contains($route->uri(), $path)) {
            echo "  ✅ $path → " . $route->getActionName() . "\n";
            $found = true;
            break;
        }
    }
    if (!$found) {
        echo "  ❌ $path → NO ENCONTRADA\n";
    }
}

echo "\n";

// 2. Ver qué hay en la base de datos
echo "📊 DATOS EN LA BASE DE DATOS:\n";
$today = now()->format('Y-m-d');
$assignments = WorkoutAssignment::with(['workout', 'athlete.user'])
    ->whereDate('scheduled_date', $today)
    ->get();

echo "  Assignments para HOY ($today): " . $assignments->count() . "\n\n";

foreach ($assignments as $a) {
    echo sprintf("  ID: %d | Workout: %s | Atleta: %s | Completado: %s\n",
        $a->id,
        $a->workout->name ?? 'NULL',
        $a->athlete->user->email ?? 'NULL',
        $a->is_completed ? 'Sí' : 'No'
    );
}

echo "\n";

// 3. Simular request del atleta
echo "🏃 SIMULANDO REQUEST DEL ATLETA:\n";
$athleteUser = User::where('email', 'atleta@coaching.com')->first();
if ($athleteUser && $athleteUser->athlete) {
    $athleteAssignments = $athleteUser->athlete->assignments()
        ->with('workout')
        ->whereDate('scheduled_date', $today)
        ->get();
    
    echo "  Assignments encontrados: " . $athleteAssignments->count() . "\n";
    foreach ($athleteAssignments as $a) {
        echo sprintf("    - ID: %d | %s | Fecha: %s\n",
            $a->id,
            $a->workout->name,
            $a->scheduled_date->format('Y-m-d')
        );
    }
} else {
    echo "  ❌ Atleta no encontrado\n";
}

echo "\n═══════════════════════════════════════════\n\n";
