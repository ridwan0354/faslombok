<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScoreItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'submission_id',
        'criterion_id',
        'rating_option_id',
        'numeric_value',
        'weighted_score',
        'comment',
    ];

    protected function casts(): array
    {
        return [
            'numeric_value' => 'decimal:2',
            'weighted_score' => 'decimal:2',
        ];
    }

    public function submission()
    {
        return $this->belongsTo(ScoreSubmission::class, 'submission_id');
    }

    public function criterion()
    {
        return $this->belongsTo(RubricCriterion::class, 'criterion_id');
    }

    public function ratingOption()
    {
        return $this->belongsTo(RatingOption::class, 'rating_option_id');
    }
}
