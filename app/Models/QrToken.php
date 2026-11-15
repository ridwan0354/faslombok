<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QrToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'entity_type',
        'entity_id',
        'token_hash',
        'token_plain',
        'issued_at',
        'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function isValid(): bool
    {
        return is_null($this->revoked_at);
    }

    public function participant()
    {
        return $this->belongsTo(Participant::class, 'entity_id');
    }

    public function team()
    {
        return $this->belongsTo(Team::class, 'entity_id');
    }
}
