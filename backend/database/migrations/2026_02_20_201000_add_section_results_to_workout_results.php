<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workout_results', function (Blueprint $table) {
            // Agregar columna section_results si no existe
            if (!Schema::hasColumn('workout_results', 'section_results')) {
                $table->json('section_results')->nullable()->after('notes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('workout_results', function (Blueprint $table) {
            $table->dropColumn('section_results');
        });
    }
};
