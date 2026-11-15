<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GradeLevel extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'code',
        'name',
        'sort_order',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function participants()
    {
        return $this->hasMany(Participant::class, 'grade_id');
    }

    public function competitions()
    {
        return $this->belongsToMany(Competition::class, 'competition_grade_levels');
    }
}
