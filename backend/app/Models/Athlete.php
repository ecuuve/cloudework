<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Athlete extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'coach_id',
        'status',
    ];

    /**
     * Get the user that owns the athlete profile.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the coach that manages this athlete.
     */
    public function coach(): BelongsTo
    {
        return $this->belongsTo(Coach::class);
    }

    /**
     * Get the workout assignments for the athlete.
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(WorkoutAssignment::class, 'athlete_id');
    }

    /**
     * Get the workout results for the athlete.
     */
    public function results(): HasMany
    {
        return $this->hasMany(WorkoutResult::class, 'athlete_id');
    }

    /**
     * Get the personal records for the athlete.
     */
    public function personalRecords(): HasMany
    {
        return $this->hasMany(PersonalRecord::class, 'athlete_id');
    }

    /**
     * Scope a query to only include active athletes.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
