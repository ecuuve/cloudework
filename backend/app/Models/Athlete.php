<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Athlete extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'coach_id',
        'date_of_birth',
        'gender',
        'height_cm',
        'weight_kg',
        'goals',
        'medical_notes',
        'emergency_contact_name',
        'emergency_contact_phone',
        'start_date',
        'status',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'height_cm' => 'decimal:2',
            'weight_kg' => 'decimal:2',
            'start_date' => 'date',
        ];
    }

    /**
     * Get the user that owns the athlete.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the coach that manages the athlete.
     */
    public function coach()
    {
        return $this->belongsTo(Coach::class);
    }

    /**
     * Get the workout assignments for the athlete.
     */
    public function assignments()
    {
        return $this->hasMany(WorkoutAssignment::class);
    }

    /**
     * Get the workout results for the athlete.
     */
    public function results()
    {
        return $this->hasMany(WorkoutResult::class);
    }

    /**
     * Get the personal records for the athlete.
     */
    public function personalRecords()
    {
        return $this->hasMany(PersonalRecord::class);
    }

    /**
     * Get the groups the athlete belongs to.
     */
    public function groups()
    {
        return $this->belongsToMany(AthleteGroup::class, 'athlete_group_members');
    }

    /**
     * Get the progress snapshots for the athlete.
     */
    public function progressSnapshots()
    {
        return $this->hasMany(AthleteProgressSnapshot::class);
    }

    /**
     * Calculate athlete's age.
     */
    public function getAgeAttribute(): ?int
    {
        return $this->date_of_birth ? $this->date_of_birth->age : null;
    }

    /**
     * Get total workouts completed.
     */
    public function getTotalWorkoutsAttribute(): int
    {
        return $this->results()->count();
    }

    /**
     * Get total PRs achieved.
     */
    public function getTotalPRsAttribute(): int
    {
        return $this->personalRecords()->count();
    }

    /**
     * Get current workout streak (consecutive days).
     */
    public function getCurrentStreakAttribute(): int
    {
        $results = $this->results()
            ->orderBy('completed_at', 'desc')
            ->get()
            ->groupBy(function($result) {
                return $result->completed_at->format('Y-m-d');
            });

        $streak = 0;
        $currentDate = now()->startOfDay();

        foreach ($results as $date => $dayResults) {
            $resultDate = $dayResults->first()->completed_at->startOfDay();
            
            if ($resultDate->equalTo($currentDate) || $resultDate->equalTo($currentDate->subDay())) {
                $streak++;
                $currentDate = $resultDate;
            } else {
                break;
            }
        }

        return $streak;
    }

    /**
     * Get completion rate percentage.
     */
    public function getCompletionRateAttribute(): float
    {
        $totalAssignments = $this->assignments()->count();
        
        if ($totalAssignments === 0) {
            return 0;
        }

        $completedAssignments = $this->assignments()
            ->where('is_completed', true)
            ->count();

        return round(($completedAssignments / $totalAssignments) * 100, 2);
    }

    /**
     * Scope a query to only include active athletes.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to only include inactive athletes.
     */
    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }
}
