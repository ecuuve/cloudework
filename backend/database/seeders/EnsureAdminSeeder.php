<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Coach;
use App\Models\Athlete;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * EnsureAdminSeeder
 *
 * Crea (o actualiza) usuarios de prueba:
 *   - coach@coaching.com / coaching123  (rol: coach)
 *   - atleta@coaching.com / coaching123 (rol: athlete)
 *
 * Es IDEMPOTENTE: si ya existen, solo actualiza contraseñas.
 *
 * CÓMO CORRER:
 *   php artisan db:seed --class=EnsureAdminSeeder
 */
class EnsureAdminSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->newLine();
        $this->command->info('══════════════════════════════════════════════════');
        $this->command->info('  COACHING — Creando usuarios de prueba');
        $this->command->info('══════════════════════════════════════════════════');

        // ── 1. COACH ───────────────────────────────────────────────────────
        $this->command->info('');
        $this->command->info('👤 Coach...');

        $coachUser = User::updateOrCreate(
            ['email' => 'coach@coaching.com'],
            [
                'password'          => Hash::make('coaching123'),
                'role'              => 'coach',
                'first_name'        => 'Admin',
                'last_name'         => 'Coach',
                'is_active'         => true,
                'email_verified_at' => now(),
            ]
        );

        $coach = Coach::updateOrCreate(
            ['user_id' => $coachUser->id],
            [
                'certification_level'     => 'CF-L1',
                'years_experience'        => 1,
                'bio'                     => 'Coach principal del sistema',
                'specialties'             => ['CrossFit', 'Strength & Conditioning'],
                'subscription_status'     => 'active',
                'subscription_plan'       => 'pro',
                'subscription_start_date' => now(),
                'subscription_end_date'   => now()->addYears(10),
                'max_athletes'            => 999,
            ]
        );

        $coachUser->tokens()->delete();
        $action = $coachUser->wasRecentlyCreated ? 'creado' : 'actualizado';
        $this->command->info("  ✅ {$action}: coach@coaching.com");

        // ── 2. ATLETA DE PRUEBA ────────────────────────────────────────────
        $this->command->info('');
        $this->command->info('🏋️ Atleta...');

        $athleteUser = User::updateOrCreate(
            ['email' => 'atleta@coaching.com'],
            [
                'password'          => Hash::make('coaching123'),
                'role'              => 'athlete',
                'first_name'        => 'Test',
                'last_name'         => 'Atleta',
                'is_active'         => true,
                'email_verified_at' => now(),
            ]
        );

        Athlete::updateOrCreate(
            ['user_id' => $athleteUser->id],
            [
                'coach_id'   => $coach->id,
                'gender'     => 'male',
                'status'     => 'active',
                'start_date' => now(),
            ]
        );

        $athleteUser->tokens()->delete();
        $action2 = $athleteUser->wasRecentlyCreated ? 'creado' : 'actualizado';
        $this->command->info("  ✅ {$action2}: atleta@coaching.com");

        // ── Resumen ────────────────────────────────────────────────────────
        $this->command->newLine();
        $this->command->info('══════════════════════════════════════════════════');
        $this->command->info('  ✅  USUARIOS LISTOS');
        $this->command->info('══════════════════════════════════════════════════');
        $this->command->newLine();
        $this->command->info('  COACH (dashboard de gestión):');
        $this->command->info('    Email:    coach@coaching.com');
        $this->command->info('    Password: coaching123');
        $this->command->info('    → Redirige a: dashboard-connected.html');
        $this->command->newLine();
        $this->command->info('  ATLETA (dashboard del atleta):');
        $this->command->info('    Email:    atleta@coaching.com');
        $this->command->info('    Password: coaching123');
        $this->command->info('    → Redirige a: athlete-dashboard.html');
        $this->command->newLine();
        $this->command->info('  🌐 Login: http://localhost:8000/demo/login-connected.html');
        $this->command->info('══════════════════════════════════════════════════');
        $this->command->newLine();
    }
}
