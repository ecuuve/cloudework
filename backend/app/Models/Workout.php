<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Workout extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
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
        'estimated_duration_minutes',
        'workout_structure',
        'scaling_options',
        'equipment_needed',
        'tags',
        'notes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
            'is_benchmark' => 'boolean',
            'estimated_duration_minutes' => 'integer',
            'workout_structure' => 'array',
            'scaling_options' => 'array',
            'equipment_needed' => 'array',
            'tags' => 'array',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($workout) {
            if (empty($workout->slug)) {
                $workout->slug = Str::slug($workout->name);
            }
        });
    }

    /**
     * Get the coach that created the workout.
     */
    public function createdBy()
    {
        return $this->belongsTo(Coach::class, 'created_by_coach_id');
    }

    /**
     * Get the assignments for the workout.
     */
    public function assignments()
    {
        return $this->hasMany(WorkoutAssignment::class);
    }

    /**
     * Get the results for the workout.
     */
    public function results()
    {
        return $this->hasMany(WorkoutResult::class);
    }

    /**
     * Get workout format display name.
     */
    public function getFormatDisplayAttribute(): string
    {
        $format = $this->workout_structure['format'] ?? 'unknown';
        
        return match($format) {
            'for_time' => 'For Time',
            'amrap' => 'AMRAP',
            'emom' => 'EMOM',
            'tabata' => 'Tabata',
            'rounds' => 'Rounds',
            'chipper' => 'Chipper',
            default => ucfirst($format),
        };
    }

    /**
     * Get times this workout has been assigned.
     */
    public function getTimesAssignedAttribute(): int
    {
        return $this->assignments()->count();
    }

    /**
     * Get average completion time (in seconds).
     */
    public function getAverageTimeAttribute(): ?int
    {
        return $this->results()
            ->whereNotNull('time_seconds')
            ->avg('time_seconds');
    }

    /**
     * Scope a query to only include public workouts.
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope a query to only include benchmarks.
     */
    public function scopeBenchmarks($query)
    {
        return $query->where('is_benchmark', true);
    }

    /**
     * Scope a query to filter by workout type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('workout_type', $type);
    }

    /**
     * Scope a query to filter by difficulty.
     */
    public function scopeOfDifficulty($query, string $difficulty)
    {
        return $query->where('difficulty_level', $difficulty);
    }

    /**
     * Scope a query to search by name or description.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%");
        });
    }
}
