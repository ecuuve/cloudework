<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\PersonalRecord;
use App\Models\WorkoutResult;
use App\Models\Athlete;

echo "\n📊 DIAGNÓSTICO DE PRs\n";
echo "══════════════════════════════════════════\n\n";

$athlete = Athlete::whereHas('user', fn($q) => $q->where('email', 'atleta@coaching.com'))->first();

if (!$athlete) {
    echo "❌ No existe atleta@coaching.com\n";
    exit;
}

echo "✅ Atleta: {$athlete->user->email} (ID: {$athlete->id})\n\n";

// Ver resultados
echo "📋 WORKOUT RESULTS:\n";
$results = WorkoutResult::where('athlete_id', $athlete->id)->with('workout')->get();
if ($results->isEmpty()) {
    echo "  ❌ No hay resultados\n";
} else {
    foreach ($results as $r) {
        echo sprintf("  ID: %d | %s | Time: %s | RX: %s | PR: %s | Fecha: %s\n",
            $r->id,
            $r->workout->name ?? 'NULL',
            $r->time_seconds ? gmdate('i:s', $r->time_seconds) : 'NULL',
            $r->rx_or_scaled,
            $r->is_pr ? 'SÍ' : 'NO',
            $r->completed_at->format('Y-m-d H:i')
        );
    }
}

echo "\n🏆 PERSONAL RECORDS:\n";
$prs = PersonalRecord::where('athlete_id', $athlete->id)->get();
if ($prs->isEmpty()) {
    echo "  ❌ No hay PRs guardados\n";
    echo "\n💡 Los PRs deberían crearse automáticamente cuando:\n";
    echo "   - Es la primera vez que haces un workout\n";
    echo "   - Mejoras tu tiempo anterior en ese workout\n";
} else {
    foreach ($prs as $pr) {
        echo sprintf("  %s: %s (%s) - %s\n",
            $pr->movement_name,
            $pr->value,
            $pr->unit,
            $pr->achieved_at->format('Y-m-d')
        );
    }
}

echo "\n══════════════════════════════════════════\n\n";
