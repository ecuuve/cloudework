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
        Schema::create('personal_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('athlete_id')->constrained()->onDelete('cascade');
            $table->string('movement_name', 200);
            $table->enum('record_type', ['weight', 'time', 'reps', 'distance'])->default('time');
            $table->decimal('value', 10, 2);
            $table->string('unit', 20);
            $table->foreignId('workout_result_id')->nullable()->constrained()->onDelete('set null');
            $table->timestamp('achieved_at');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('athlete_id');
            $table->index('movement_name');
            $table->index('record_type');
            $table->index('achieved_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('personal_records');
    }
};
