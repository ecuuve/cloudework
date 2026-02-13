<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workouts', function (Blueprint $table) {
            if (!Schema::hasColumn('workouts', 'sections')) {
                $table->json('sections')->nullable()->after('description');
            }
            if (!Schema::hasColumn('workouts', 'mindset_intention')) {
                $table->text('mindset_intention')->nullable()->after('description');
            }
        });
    }

    public function down(): void
    {
        Schema::table('workouts', function (Blueprint $table) {
            if (Schema::hasColumn('workouts', 'sections')) {
                $table->dropColumn('sections');
            }
            if (Schema::hasColumn('workouts', 'mindset_intention')) {
                $table->dropColumn('mindset_intention');
            }
        });
    }
};
