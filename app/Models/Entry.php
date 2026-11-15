<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Entry extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'competition_id',
        'participant_id',
        'team_id',
        'queue_number',
        'status',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function competition()
    {
        return $this->belongsTo(Competition::class);
    }

    public function participant()
    {
        return $this->belongsTo(Participant::class);
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function scoreSubmissions()
    {
        return $this->hasMany(ScoreSubmission::class);
    }

    public function getDisplayNameAttribute(): string
    {
        if ($this->team_id && $this->team) {
            return $this->team->name;
        }

        if ($this->participant_id && $this->participant) {
            return $this->participant->name;
        }

        return 'Entry #' . $this->id;
    }

    public function getGroupAttribute()
    {
        if ($this->team_id && $this->team) {
            return $this->team->group;
        }

        if ($this->participant_id && $this->participant) {
            return $this->participant->group;
        }

        return null;
    }

    public function getEntityCodeAttribute(): string
    {
        if ($this->team_id && $this->team) {
            return $this->team->team_code;
        }

        if ($this->participant_id && $this->participant) {
            return $this->participant->participant_code;
        }

        return '-';
    }
}
