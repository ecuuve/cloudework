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
        Schema::create('athlete_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coach_id')->constrained()->onDelete('cascade');
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->string('color', 7)->default('#FF6B35');
            $table->timestamps();

            $table->index('coach_id');
        });

        Schema::create('athlete_group_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('athlete_group_id')->constrained()->onDelete('cascade');
            $table->foreignId('athlete_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->unique(['athlete_group_id', 'athlete_id']);
            $table->index('athlete_group_id');
            $table->index('athlete_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('athlete_group_members');
        Schema::dropIfExists('athlete_groups');
    }
};
