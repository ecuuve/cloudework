<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('workouts', function (Blueprint $table) {
            $table->id();
            $table->string('name', 200);
            $table->string('slug', 200)->unique();
            $table->text('description');
            $table->enum('workout_type', ['metcon', 'strength', 'skill', 'benchmark', 'custom'])->default('custom');
            $table->enum('benchmark_category', ['girl', 'hero', 'open', 'games', 'other'])->nullable();
            $table->enum('difficulty_level', ['beginner', 'intermediate', 'rx', 'advanced'])->default('intermediate');
            $table->foreignId('created_by_coach_id')->nullable()->constrained('coaches')->onDelete('set null');
            $table->boolean('is_public')->default(false);
            $table->boolean('is_benchmark')->default(false);
            $table->integer('estimated_duration_minutes')->nullable();
            $table->json('workout_structure');
            $table->json('scaling_options')->nullable();
            $table->json('equipment_needed')->nullable();
            $table->json('tags')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('name');
            $table->index('slug');
            $table->index('workout_type');
            $table->index('difficulty_level');
            $table->index('is_public');
            $table->index('is_benchmark');
            $table->index('created_by_coach_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workouts');
    }
};
