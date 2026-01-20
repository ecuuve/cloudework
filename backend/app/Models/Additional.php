<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PersonalRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'athlete_id',
        'movement_name',
        'record_type',
        'value',
        'unit',
        'workout_result_id',
        'achieved_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'achieved_at' => 'datetime',
        ];
    }

    public function athlete()
    {
        return $this->belongsTo(Athlete::class);
    }

    public function workoutResult()
    {
        return $this->belongsTo(WorkoutResult::class);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('record_type', $type);
    }

    public function scopeForMovement($query, string $movement)
    {
        return $query->where('movement_name', $movement);
    }
}

class AthleteGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'coach_id',
        'name',
        'description',
        'color',
    ];

    public function coach()
    {
        return $this->belongsTo(Coach::class);
    }

    public function athletes()
    {
        return $this->belongsToMany(Athlete::class, 'athlete_group_members')
                    ->withTimestamps();
    }

    public function assignments()
    {
        return $this->hasMany(WorkoutAssignment::class, 'group_id');
    }

    public function getMemberCountAttribute(): int
    {
        return $this->athletes()->count();
    }
}

class AthleteProgressSnapshot extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'athlete_id',
        'snapshot_date',
        'weight_kg',
        'body_fat_percentage',
        'measurements',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'snapshot_date' => 'date',
            'weight_kg' => 'decimal:2',
            'body_fat_percentage' => 'decimal:2',
            'measurements' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function athlete()
    {
        return $this->belongsTo(Athlete::class);
    }
}
