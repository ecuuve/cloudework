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
        Schema::create('athlete_progress_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('athlete_id')->constrained()->onDelete('cascade');
            $table->date('snapshot_date');
            $table->decimal('weight_kg', 5, 2)->nullable();
            $table->decimal('body_fat_percentage', 5, 2)->nullable();
            $table->json('measurements')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('created_at');

            $table->index('athlete_id');
            $table->index('snapshot_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('athlete_progress_snapshots');
    }
};
