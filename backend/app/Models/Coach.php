<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coach extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'certification_level',
        'certification_number',
        'certification_expiry',
        'bio',
        'specialties',
        'years_experience',
        'subscription_status',
        'subscription_plan',
        'subscription_start_date',
        'subscription_end_date',
        'max_athletes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'certification_expiry' => 'date',
            'specialties' => 'array',
            'years_experience' => 'integer',
            'subscription_start_date' => 'date',
            'subscription_end_date' => 'date',
            'max_athletes' => 'integer',
        ];
    }

    /**
     * Get the user that owns the coach.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the athletes for the coach.
     */
    public function athletes()
    {
        return $this->hasMany(Athlete::class);
    }

    /**
     * Get the workouts created by the coach.
     */
    public function workouts()
    {
        return $this->hasMany(Workout::class, 'created_by_coach_id');
    }

    /**
     * Get the groups created by the coach.
     */
    public function groups()
    {
        return $this->hasMany(AthleteGroup::class);
    }

    /**
     * Get the workout assignments made by the coach.
     */
    public function assignments()
    {
        return $this->hasMany(WorkoutAssignment::class, 'assigned_by_coach_id');
    }

    /**
     * Check if coach can add more athletes.
     */
    public function canAddAthletes(): bool
    {
        return $this->athletes()->count() < $this->max_athletes;
    }

    /**
     * Check if subscription is active.
     */
    public function hasActiveSubscription(): bool
    {
        return $this->subscription_status === 'active' &&
               $this->subscription_end_date &&
               $this->subscription_end_date->isFuture();
    }

    /**
     * Scope a query to only include coaches with active subscriptions.
     */
    public function scopeActiveSubscription($query)
    {
        return $query->where('subscription_status', 'active')
                     ->where('subscription_end_date', '>', now());
    }

    /**
     * Get remaining athlete slots.
     */
    public function getRemainingSlots(): int
    {
        return $this->max_athletes - $this->athletes()->count();
    }
}
