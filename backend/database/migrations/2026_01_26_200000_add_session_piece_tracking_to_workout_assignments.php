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
        Schema::table('workout_assignments', function (Blueprint $table) {
            // Check if columns don't exist before adding
            if (!Schema::hasColumn('workout_assignments', 'session_number')) {
                $table->integer('session_number')->default(1)->after('workout_id');
            }
            
            if (!Schema::hasColumn('workout_assignments', 'piece_number')) {
                $table->integer('piece_number')->default(1)->after('session_number');
            }
            
            if (!Schema::hasColumn('workout_assignments', 'is_completed')) {
                $table->boolean('is_completed')->default(false)->after('scheduled_date');
            }
            
            if (!Schema::hasColumn('workout_assignments', 'completed_at')) {
                $table->timestamp('completed_at')->nullable()->after('is_completed');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workout_assignments', function (Blueprint $table) {
            $table->dropColumn(['session_number', 'piece_number', 'is_completed', 'completed_at']);
        });
    }
};
