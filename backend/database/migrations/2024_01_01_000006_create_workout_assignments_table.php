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
        Schema::create('workout_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workout_id')->constrained()->onDelete('cascade');
            $table->foreignId('assigned_by_coach_id')->constrained('coaches')->onDelete('cascade');
            $table->foreignId('athlete_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('group_id')->nullable()->constrained('athlete_groups')->onDelete('cascade');
            $table->date('scheduled_date');
            $table->text('notes')->nullable();
            $table->boolean('is_completed')->default(false);
            $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
            $table->timestamps();

            $table->index('workout_id');
            $table->index('assigned_by_coach_id');
            $table->index('athlete_id');
            $table->index('group_id');
            $table->index('scheduled_date');
            $table->index('is_completed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workout_assignments');
    }
};
