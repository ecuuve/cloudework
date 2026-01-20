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
        Schema::create('workout_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->nullable()->constrained('workout_assignments')->onDelete('set null');
            $table->foreignId('athlete_id')->constrained()->onDelete('cascade');
            $table->foreignId('workout_id')->constrained()->onDelete('cascade');
            $table->timestamp('completed_at');
            $table->json('result_data')->nullable();
            $table->integer('time_seconds')->nullable();
            $table->integer('rounds_completed')->nullable();
            $table->integer('reps_completed')->nullable();
            $table->json('weight_used')->nullable();
            $table->enum('rx_or_scaled', ['rx', 'scaled'])->default('scaled');
            $table->integer('feeling_rating')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_pr')->default(false);
            $table->string('video_url')->nullable();
            $table->timestamps();

            $table->index('assignment_id');
            $table->index('athlete_id');
            $table->index('workout_id');
            $table->index('completed_at');
            $table->index('is_pr');
            $table->index('rx_or_scaled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workout_results');
    }
};
