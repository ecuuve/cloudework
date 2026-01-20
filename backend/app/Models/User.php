<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'email',
        'password',
        'role',
        'first_name',
        'last_name',
        'phone',
        'avatar_url',
        'timezone',
        'language',
        'is_active',
        'email_verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the coach record associated with the user.
     */
    public function coach()
    {
        return $this->hasOne(Coach::class);
    }

    /**
     * Get the athlete record associated with the user.
     */
    public function athlete()
    {
        return $this->hasOne(Athlete::class);
    }

    /**
     * Get the user's full name.
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * Check if user is a coach.
     */
    public function isCoach(): bool
    {
        return $this->role === 'coach';
    }

    /**
     * Check if user is an athlete.
     */
    public function isAthlete(): bool
    {
        return $this->role === 'athlete';
    }

    /**
     * Scope a query to only include coaches.
     */
    public function scopeCoaches($query)
    {
        return $query->where('role', 'coach');
    }

    /**
     * Scope a query to only include athletes.
     */
    public function scopeAthletes($query)
    {
        return $query->where('role', 'athlete');
    }

    /**
     * Scope a query to only include active users.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
