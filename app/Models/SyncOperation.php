<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SyncOperation extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_uuid',
        'actor_id',
        'resource_version',
        'request_digest',
        'status',
        'rejection_reason',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'processed_at' => 'datetime',
        ];
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
