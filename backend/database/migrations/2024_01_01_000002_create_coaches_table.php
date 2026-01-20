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
        Schema::create('coaches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('certification_level', 100)->nullable();
            $table->string('certification_number', 100)->nullable();
            $table->date('certification_expiry')->nullable();
            $table->text('bio')->nullable();
            $table->json('specialties')->nullable();
            $table->integer('years_experience')->default(0);
            $table->enum('subscription_status', ['trial', 'active', 'expired', 'cancelled'])->default('trial');
            $table->enum('subscription_plan', ['basic', 'pro', 'premium'])->default('basic');
            $table->date('subscription_start_date')->nullable();
            $table->date('subscription_end_date')->nullable();
            $table->integer('max_athletes')->default(5);
            $table->timestamps();

            $table->index('user_id');
            $table->index('subscription_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coaches');
    }
};
