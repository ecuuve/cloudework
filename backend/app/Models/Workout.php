<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Workout extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'workout_type',
        'benchmark_category',
        'difficulty_level',
        'created_by_coach_id',
        'is_public',
        'is_benchmark',
        'is_hero',
        'estimated_duration_minutes',
        'workout_structure',
        'scaling_options',
        'equipment_needed',
        'tags',
        'notes',
        'sections',
        'mindset_intention',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'is_benchmark' => 'boolean',
        'is_hero' => 'boolean',
        'sections' => 'array',
        'tags' => 'array',
        'workout_structure' => 'array',
        'scaling_options' => 'array',
    ];

    // Relationships
    public function coach(): BelongsTo
    {
        return $this->belongsTo(Coach::class, 'created_by_coach_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(WorkoutAssignment::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(WorkoutResult::class);
    }
}
