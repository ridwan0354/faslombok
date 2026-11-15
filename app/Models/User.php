<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'password',
        'role',
        'status',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isMaster(): bool
    {
        return $this->role === 'master';
    }

    public function isJudge(): bool
    {
        return $this->role === 'judge';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function judgeAssignments()
    {
        return $this->hasMany(JudgeAssignment::class);
    }

    public function eventUsers()
    {
        return $this->hasMany(EventUser::class);
    }

    public function events()
    {
        return $this->belongsToMany(Event::class, 'event_users')->withPivot('event_role')->withTimestamps();
    }

    public function auditLogs()
    {
        return $this->hasMany(ScoreAuditLog::class, 'actor_id');
    }
}
