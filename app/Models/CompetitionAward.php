<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompetitionAward extends Model
{
    use HasFactory;

    protected $fillable = [
        'competition_id',
        'rank_position',
        'rank_label',
        'points_for_group',
    ];

    public function competition()
    {
        return $this->belongsTo(Competition::class);
    }
}
