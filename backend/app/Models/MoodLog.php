<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MoodLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'athlete_id',
        'mood_level',
        'notes',
    ];

    protected $casts = [
        'mood_level' => 'integer',
        'created_at' => 'datetime',
    ];

    public function athlete()
    {
        return $this->belongsTo(Athlete::class);
    }

    // Helper para obtener emoji según mood_level
    public function getMoodEmojiAttribute(): string
    {
        return match($this->mood_level) {
            7 => '😄',
            6 => '🙂',
            5 => '😊',
            4 => '😐',
            3 => '😕',
            2 => '☹️',
            1 => '😞',
            default => '😐',
        };
    }

    public function getMoodLabelAttribute(): string
    {
        return match($this->mood_level) {
            7 => 'Excelente',
            6 => 'Muy bien',
            5 => 'Bien',
            4 => 'Normal',
            3 => 'Regular',
            2 => 'Mal',
            1 => 'Muy mal',
            default => 'Normal',
        };
    }
}
