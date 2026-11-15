<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RatingOption extends Model
{
    use HasFactory;

    protected $fillable = [
        'rubric_id',
        'label',
        'numeric_value',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'numeric_value' => 'decimal:2',
        ];
    }

    public function rubric()
    {
        return $this->belongsTo(Rubric::class);
    }
}
