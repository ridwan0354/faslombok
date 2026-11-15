<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResultPublication extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'competition_id',
        'version',
        'payload_snapshot',
        'published_by',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'payload_snapshot' => 'array',
            'published_at' => 'datetime',
        ];
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function competition()
    {
        return $this->belongsTo(Competition::class);
    }

    public function publisher()
    {
        return $this->belongsTo(User::class, 'published_by');
    }
}
