<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RubricCriterion extends Model
{
    use HasFactory;

    protected $fillable = [
        'rubric_id',
        'title',
        'weight_pct',
        'sort_order',
        'required',
    ];

    protected function casts(): array
    {
        return [
            'weight_pct' => 'decimal:2',
            'required' => 'boolean',
        ];
    }

    public function rubric()
    {
        return $this->belongsTo(Rubric::class);
    }

    public function scoreItems()
    {
        return $this->hasMany(ScoreItem::class, 'criterion_id');
    }
}
