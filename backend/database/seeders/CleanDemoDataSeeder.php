<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * CleanDemoDataSeeder
 *
 * Elimina TODOS los datos dummy creados por DemoSeeder.
 * Preserva la estructura de tablas y los usuarios que TÚ hayas creado
 * después del seeding (si los hay).
 *
 * CÓMO CORRER:
 *   php artisan db:seed --class=CleanDemoDataSeeder
 *
 * O para empezar completamente desde cero (borra TODO y re-crea tablas):
 *   php artisan migrate:fresh
 *   (Luego vuelves a crear tu usuario coach con tinker o registrándote)
 */
class CleanDemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🧹 Limpiando datos demo...');

        // Desactivar foreign key checks para poder borrar en cualquier orden
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // ── Resultados y PRs ───────────────────────────────────────────────
        $prs      = DB::table('personal_records')->count();
        $results  = DB::table('workout_results')->count();
        DB::table('personal_records')->truncate();
        DB::table('workout_results')->truncate();
        $this->command->info("  ✅ Personal Records eliminados: {$prs}");
        $this->command->info("  ✅ Workout Results eliminados: {$results}");

        // ── Asignaciones ───────────────────────────────────────────────────
        $assignments = DB::table('workout_assignments')->count();
        DB::table('workout_assignments')->truncate();
        $this->command->info("  ✅ Workout Assignments eliminados: {$assignments}");

        // ── Workouts ───────────────────────────────────────────────────────
        $workouts = DB::table('workouts')->count();
        DB::table('workouts')->truncate();
        $this->command->info("  ✅ Workouts eliminados: {$workouts}");

        // ── Grupos de atletas ──────────────────────────────────────────────
        if (DB::getSchemaBuilder()->hasTable('athlete_group_members')) {
            DB::table('athlete_group_members')->truncate();
        }
        if (DB::getSchemaBuilder()->hasTable('athlete_groups')) {
            $groups = DB::table('athlete_groups')->count();
            DB::table('athlete_groups')->truncate();
            $this->command->info("  ✅ Athlete Groups eliminados: {$groups}");
        }

        // ── Atletas y sus usuarios ─────────────────────────────────────────
        $athleteUserIds = DB::table('athletes')
            ->join('users', 'athletes.user_id', '=', 'users.id')
            ->where('users.role', 'athlete')
            ->pluck('users.id')
            ->toArray();

        $athletes = DB::table('athletes')->count();
        DB::table('athletes')->truncate();
        $this->command->info("  ✅ Athletes eliminados: {$athletes}");

        if (count($athleteUserIds)) {
            DB::table('users')->whereIn('id', $athleteUserIds)->delete();
            $this->command->info("  ✅ Usuarios atleta eliminados: " . count($athleteUserIds));
        }

        // ── Coaches y sus usuarios ─────────────────────────────────────────
        // IMPORTANTE: Solo elimina el coach de demo. Si ya creaste tu propio
        // coach, NO lo elimina (lo detecta por email demo@cloudework.com o demo@coaching.com)
        $demoCoachEmails = ['demo@cloudework.com', 'demo@coaching.com'];

        $demoCoachUserIds = DB::table('users')
            ->whereIn('email', $demoCoachEmails)
            ->pluck('id')
            ->toArray();

        if (count($demoCoachUserIds)) {
            // Eliminar coach records asociados
            DB::table('coaches')
                ->whereIn('user_id', $demoCoachUserIds)
                ->delete();

            // Eliminar los usuarios demo
            DB::table('users')
                ->whereIn('id', $demoCoachUserIds)
                ->delete();

            $this->command->info('  ✅ Usuario demo coach eliminado');
        } else {
            $this->command->warn('  ℹ️  No se encontró usuario demo coach (ya fue eliminado o nunca existió)');
        }

        // ── Mensajes / progress snapshots ──────────────────────────────────
        if (DB::getSchemaBuilder()->hasTable('messages')) {
            DB::table('messages')->truncate();
        }
        if (DB::getSchemaBuilder()->hasTable('athlete_progress_snapshots')) {
            DB::table('athlete_progress_snapshots')->truncate();
        }

        // Reactivar foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->command->newLine();
        $this->command->info('✅ LIMPIEZA COMPLETA');
        $this->command->newLine();
        $this->command->info('📋 SIGUIENTE PASO:');
        $this->command->info('   Regístrate como coach en: http://localhost:8000/demo/register.html');
        $this->command->info('   O usa tinker para crear tu usuario:');
        $this->command->line('   php artisan tinker');
        $this->command->line('   >>> App\Models\User::create([...])');
        $this->command->newLine();
    }
}
