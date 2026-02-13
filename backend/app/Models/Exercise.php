<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Exercise extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'category',
        'description',
        'video_url',
        'equipment',
        'difficulty_level',
        'muscle_groups',
        'created_by_coach_id',
        'is_public',
    ];

    protected function casts(): array
    {
        return [
            'equipment' => 'array',
            'muscle_groups' => 'array',
            'is_public' => 'boolean',
        ];
    }

    public function coach()
    {
        return $this->belongsTo(Coach::class, 'created_by_coach_id');
    }
}
