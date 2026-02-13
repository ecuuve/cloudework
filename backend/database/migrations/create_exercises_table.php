<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exercises', function (Blueprint $table) {
            $table->id();
            $table->string('name', 200);
            $table->string('category', 50)->nullable(); // strength, cardio, gymnastics, mobility, etc
            $table->text('description')->nullable();
            $table->string('video_url')->nullable();
            $table->json('equipment')->nullable(); // ['barbell', 'rings', 'box']
            $table->string('difficulty_level', 20)->default('intermediate'); // beginner, intermediate, advanced
            $table->json('muscle_groups')->nullable(); // ['legs', 'back', 'core']
            $table->foreignId('created_by_coach_id')->nullable()->constrained('coaches')->onDelete('set null');
            $table->boolean('is_public')->default(true);
            $table->timestamps();
            
            $table->index('category');
            $table->index('difficulty_level');
            $table->index('is_public');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exercises');
    }
};
