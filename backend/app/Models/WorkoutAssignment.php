<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkoutAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'workout_id',
        'assigned_by_coach_id',
        'athlete_id',
        'group_id',
        'scheduled_date',
        'notes',
        'is_completed',
        'priority',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_date' => 'date',
            'is_completed' => 'boolean',
        ];
    }

    public function workout()
    {
        return $this->belongsTo(Workout::class);
    }

    public function assignedBy()
    {
        return $this->belongsTo(Coach::class, 'assigned_by_coach_id');
    }

    public function athlete()
    {
        return $this->belongsTo(Athlete::class);
    }

    public function group()
    {
        return $this->belongsTo(AthleteGroup::class);
    }

    public function result()
    {
        return $this->hasOne(WorkoutResult::class, 'assignment_id');
    }

    public function scopeCompleted($query)
    {
        return $query->where('is_completed', true);
    }

    public function scopePending($query)
    {
        return $query->where('is_completed', false);
    }

    public function scopeScheduledFor($query, $date)
    {
        return $query->whereDate('scheduled_date', $date);
    }
}
