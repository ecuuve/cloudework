<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workout_results', function (Blueprint $table) {
            // Hacer completed_at nullable si no lo es
            $table->timestamp('completed_at')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('workout_results', function (Blueprint $table) {
            $table->timestamp('completed_at')->nullable(false)->change();
        });
    }
};
