<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Participant extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'group_id',
        'grade_id',
        'participant_code',
        'name',
        'gender',
        'birth_date',
        'phone',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'active' => 'boolean',
        ];
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function gradeLevel()
    {
        return $this->belongsTo(GradeLevel::class, 'grade_id');
    }

    public function entries()
    {
        return $this->hasMany(Entry::class);
    }

    public function teams()
    {
        return $this->belongsToMany(Team::class, 'team_members')->withPivot('membership_status')->withTimestamps();
    }

    public function qrToken()
    {
        return $this->hasOne(QrToken::class, 'entity_id')->where('entity_type', 'participant');
    }
}
