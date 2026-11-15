<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JudgeAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'user_id',
        'competition_id',
        'can_view_participants',
        'can_view_scores',
        'can_view_peer_scores',
        'can_score',
        'active',
        'required_for_completion',
    ];

    protected function casts(): array
    {
        return [
            'can_view_participants' => 'boolean',
            'can_view_scores' => 'boolean',
            'can_view_peer_scores' => 'boolean',
            'can_score' => 'boolean',
            'active' => 'boolean',
            'required_for_completion' => 'boolean',
        ];
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function competition()
    {
        return $this->belongsTo(Competition::class);
    }

    public function scoreSubmissions()
    {
        return $this->hasMany(ScoreSubmission::class, 'assignment_id');
    }
}
