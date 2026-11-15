<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'enable_group_champion',
        'config_json',
    ];

    protected function casts(): array
    {
        return [
            'enable_group_champion' => 'boolean',
            'config_json' => 'array',
        ];
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}
