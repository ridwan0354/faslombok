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

    /**
     * Derive a per-user verification key tied to current credentials for signed judge access links.
     */
    public function judgeAccessKey(): string
    {
        return substr(
            hash_hmac('sha256', $this->id . '|' . $this->phone . '|' . $this->password, (string) config('app.key')),
            0,
            16
        );
    }

    /**
     * Build a signed auto-login URL so the judge can open the portal in one tap.
     */
    public function judgeMagicLoginUrl(): string
    {
        return \Illuminate\Support\Facades\URL::signedRoute('judge.magic-login', [
            'judge' => $this->id,
            'k' => $this->judgeAccessKey(),
        ]);
    }
}
