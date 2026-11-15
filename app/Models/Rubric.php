<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rubric extends Model
{
    use HasFactory;

    protected $fillable = [
        'competition_id',
        'version',
        'status',
        'locked_at',
    ];

    protected function casts(): array
    {
        return [
            'locked_at' => 'datetime',
        ];
    }

    public function competition()
    {
        return $this->belongsTo(Competition::class);
    }

    public function criteria()
    {
        return $this->hasMany(RubricCriterion::class)->orderBy('sort_order');
    }

    public function ratingOptions()
    {
        return $this->hasMany(RatingOption::class)->orderBy('sort_order');
    }

    public function scoreSubmissions()
    {
        return $this->hasMany(ScoreSubmission::class);
    }

    public function totalWeight(): float
    {
        return (float) $this->criteria->sum('weight_pct');
    }

    public function isWeightValid(): bool
    {
        return round($this->totalWeight(), 2) == 100.00;
    }
}
