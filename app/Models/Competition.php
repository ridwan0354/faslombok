<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Competition extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'name',
        'format',
        'eligibility_rule',
        'location',
        'status',
        'is_locked',
        'locked_at',
    ];

    protected function casts(): array
    {
        return [
            'is_locked' => 'boolean',
            'locked_at' => 'datetime',
        ];
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function rubrics()
    {
        return $this->hasMany(Rubric::class);
    }

    public function activeRubric()
    {
        return $this->hasOne(Rubric::class)->where('status', 'active')->latestOfMany('version');
    }

    public function entries()
    {
        return $this->hasMany(Entry::class);
    }

    public function teams()
    {
        return $this->hasMany(Team::class);
    }

    public function judgeAssignments()
    {
        return $this->hasMany(JudgeAssignment::class);
    }

    public function gradeLevels()
    {
        return $this->belongsToMany(GradeLevel::class, 'competition_grade_levels');
    }

    public function awards()
    {
        return $this->hasMany(CompetitionAward::class)->orderBy('rank_position');
    }

    public function publications()
    {
        return $this->hasMany(ResultPublication::class);
    }

    public function latestPublication()
    {
        return $this->hasOne(ResultPublication::class)->latestOfMany('version');
    }

    public function isIndividual(): bool
    {
        return $this->format === 'individual';
    }

    public function isTeam(): bool
    {
        return $this->format === 'team';
    }
}
