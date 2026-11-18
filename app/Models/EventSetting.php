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

    public static function defaultCertificateTemplate(): array
    {
        return [
            'background_image' => '/images/sample-sertifikat-a4.jpg',
            'paper_size' => 'a4',
            'orientation' => 'landscape',
            'fields' => [
                'name' => [
                    'label' => 'Nama Penerima',
                    'x' => 50,
                    'y' => 38,
                    'font_size' => 38,
                    'font_family' => "'Times New Roman', serif",
                    'font_weight' => 'bold',
                    'color' => '#1e1b4b',
                    'align' => 'center',
                    'prefix' => '',
                    'enabled' => true,
                ],
                'award' => [
                    'label' => 'Predikat / Juara',
                    'x' => 50,
                    'y' => 46,
                    'font_size' => 28,
                    'font_family' => "'Arial', sans-serif",
                    'font_weight' => 'bold',
                    'color' => '#b45309',
                    'align' => 'center',
                    'prefix' => 'Sebagai : ',
                    'enabled' => true,
                ],
                'competition' => [
                    'label' => 'Cabang Lomba',
                    'x' => 50,
                    'y' => 53,
                    'font_size' => 22,
                    'font_family' => "'Georgia', serif",
                    'font_weight' => 'bold',
                    'color' => '#334155',
                    'align' => 'center',
                    'prefix' => 'Pada Cabang Perlombaan : ',
                    'enabled' => true,
                ],
                'group' => [
                    'label' => 'Kelompok / TPQ',
                    'x' => 50,
                    'y' => 59,
                    'font_size' => 18,
                    'font_family' => "'Arial', sans-serif",
                    'font_weight' => 'normal',
                    'color' => '#475569',
                    'align' => 'center',
                    'prefix' => 'Utusan : Kelompok / TPQ ',
                    'enabled' => true,
                ],
                'number' => [
                    'label' => 'Nomor Sertifikat',
                    'x' => 50,
                    'y' => 27,
                    'font_size' => 14,
                    'font_family' => "'Arial', sans-serif",
                    'font_weight' => 'bold',
                    'color' => '#64748b',
                    'align' => 'center',
                    'prefix' => 'No: ',
                    'enabled' => true,
                ],
            ],
        ];
    }

    public function getCertificateTemplate(): array
    {
        $config = $this->config_json ?? [];
        if (!isset($config['certificate_template'])) {
            return self::defaultCertificateTemplate();
        }
        $defaults = self::defaultCertificateTemplate();
        $saved = $config['certificate_template'];
        
        // Deep merge fields
        $fields = $defaults['fields'];
        if (isset($saved['fields']) && is_array($saved['fields'])) {
            foreach ($saved['fields'] as $key => $val) {
                if (isset($fields[$key])) {
                    $fields[$key] = array_merge($fields[$key], $val);
                } else {
                    $fields[$key] = $val;
                }
            }
        }

        return [
            'background_image' => $saved['background_image'] ?? $defaults['background_image'],
            'paper_size' => $saved['paper_size'] ?? $defaults['paper_size'],
            'orientation' => $saved['orientation'] ?? $defaults['orientation'],
            'fields' => $fields,
        ];
    }
}
