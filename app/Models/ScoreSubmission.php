<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScoreSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'entry_id',
        'assignment_id',
        'rubric_id',
        'status',
        'score_total',
        'revision',
        'notes',
        'submitted_at',
        'locked_at',
    ];

    protected function casts(): array
    {
        return [
            'score_total' => 'decimal:2',
            'submitted_at' => 'datetime',
            'locked_at' => 'datetime',
        ];
    }

    public function entry()
    {
        return $this->belongsTo(Entry::class);
    }

    public function assignment()
    {
        return $this->belongsTo(JudgeAssignment::class, 'assignment_id');
    }

    public function rubric()
    {
        return $this->belongsTo(Rubric::class);
    }

    public function items()
    {
        return $this->hasMany(ScoreItem::class, 'submission_id');
    }

    public function auditLogs()
    {
        return $this->hasMany(ScoreAuditLog::class, 'submission_id');
    }
}
