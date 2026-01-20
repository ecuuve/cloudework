<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkoutResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'assignment_id',
        'athlete_id',
        'workout_id',
        'completed_at',
        'result_data',
        'time_seconds',
        'rounds_completed',
        'reps_completed',
        'weight_used',
        'rx_or_scaled',
        'feeling_rating',
        'notes',
        'is_pr',
        'video_url',
    ];

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
            'result_data' => 'array',
            'time_seconds' => 'integer',
            'rounds_completed' => 'integer',
            'reps_completed' => 'integer',
            'weight_used' => 'array',
            'feeling_rating' => 'integer',
            'is_pr' => 'boolean',
        ];
    }

    public function assignment()
    {
        return $this->belongsTo(WorkoutAssignment::class);
    }

    public function athlete()
    {
        return $this->belongsTo(Athlete::class);
    }

    public function workout()
    {
        return $this->belongsTo(Workout::class);
    }

    public function getFormattedTimeAttribute(): ?string
    {
        if (!$this->time_seconds) {
            return null;
        }

        $minutes = floor($this->time_seconds / 60);
        $seconds = $this->time_seconds % 60;

        return sprintf('%d:%02d', $minutes, $seconds);
    }

    public function scopePersonalRecords($query)
    {
        return $query->where('is_pr', true);
    }

    public function scopeRx($query)
    {
        return $query->where('rx_or_scaled', 'rx');
    }

    public function scopeCompleted Between($query, $startDate, $endDate)
    {
        return $query->whereBetween('completed_at', [$startDate, $endDate]);
    }
}
