<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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