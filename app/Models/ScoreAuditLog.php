<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScoreAuditLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'actor_id',
        'submission_id',
        'entry_id',
        'action',
        'before_json',
        'after_json',
        'reason',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'before_json' => 'array',
            'after_json' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function submission()
    {
        return $this->belongsTo(ScoreSubmission::class, 'submission_id');
    }

    public function entry()
    {
        return $this->belongsTo(Entry::class, 'entry_id');
    }
}
