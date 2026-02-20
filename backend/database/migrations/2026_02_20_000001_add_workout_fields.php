<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workouts', function (Blueprint $table) {
            // Agregar is_hero si no existe
            if (!Schema::hasColumn('workouts', 'is_hero')) {
                $table->boolean('is_hero')->default(false)->after('is_benchmark');
            }
            
            // Agregar sections JSON si no existe
            if (!Schema::hasColumn('workouts', 'sections')) {
                $table->json('sections')->nullable()->after('notes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('workouts', function (Blueprint $table) {
            $table->dropColumn(['is_hero', 'sections']);
        });
    }
};
