<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    /** Offset waktu standar WITA untuk wilayah Lombok (UTC+8). */
    public const WITA_TIMEZONE = '+08:00';

    public static function formatWita(mixed $datetime, string $format = 'd M Y, H:i'): ?string
    {
        if (!$datetime) {
            return null;
        }

        return \Carbon\Carbon::parse($datetime)->setTimezone(self::WITA_TIMEZONE)->format($format);
    }

    protected $fillable = [
        'name',
        'year',
        'start_at',
        'end_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
        ];
    }

    public function competitions()
    {
        return $this->hasMany(Competition::class);
    }

    public function groups()
    {
        return $this->hasMany(Group::class);
    }

    public function gradeLevels()
    {
        return $this->hasMany(GradeLevel::class)->orderBy('sort_order');
    }

    public function participants()
    {
        return $this->hasMany(Participant::class);
    }

    public function teams()
    {
        return $this->hasMany(Team::class);
    }

    public function entries()
    {
        return $this->hasMany(Entry::class);
    }

    public function qrTokens()
    {
        return $this->hasMany(QrToken::class);
    }

    public function judgeAssignments()
    {
        return $this->hasMany(JudgeAssignment::class);
    }

    public function settings()
    {
        return $this->hasOne(EventSetting::class);
    }

    public function publications()
    {
        return $this->hasMany(ResultPublication::class);
    }
}
