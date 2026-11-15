<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'competition_id',
        'group_id',
        'team_code',
        'name',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function competition()
    {
        return $this->belongsTo(Competition::class);
    }

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function members()
    {
        return $this->belongsToMany(Participant::class, 'team_members')->withPivot('membership_status')->withTimestamps();
    }

    public function teamMembers()
    {
        return $this->hasMany(TeamMember::class);
    }

    public function entry()
    {
        return $this->hasOne(Entry::class);
    }

    public function qrToken()
    {
        return $this->hasOne(QrToken::class, 'entity_id')->where('entity_type', 'team');
    }
}
