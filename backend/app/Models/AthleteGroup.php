<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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