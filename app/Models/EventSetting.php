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
            'background_image' => '/images/sertifikat-resmi-a4.jpg',
            'paper_size' => 'a4',
            'orientation' => 'landscape',
            'fields' => [
                'name' => [
                    'label' => 'Nama Penerima',
                    'x' => 50,
                    'y' => 40.2,
                    'font_size' => 44,
                    'font_family' => "'Baloo 2', 'Arial Rounded MT Bold', sans-serif",
                    'font_weight' => '800',
                    'font_style' => 'normal',
                    'color' => '#203060',
                    'stroke_color' => '',
                    'stroke_width' => 0,
                    'align' => 'center',
                    'prefix' => '',
                    'enabled' => true,
                ],
                'award' => [
                    'label' => 'Predikat / Juara',
                    'x' => 50,
                    'y' => 51.8,
                    'font_size' => 50,
                    'font_family' => "'Berkshire Swash', 'Playfair Display', Georgia, serif",
                    'font_weight' => '900',
                    'font_style' => 'normal',
                    'color' => '#f8a800',
                    'stroke_color' => '#5b197d',
                    'stroke_width' => 3,
                    'align' => 'center',
                    'prefix' => '',
                    'enabled' => true,
                ],
                'competition' => [
                    'label' => 'Cabang Lomba',
                    'x' => 50,
                    'y' => 58.6,
                    'font_size' => 35,
                    'font_family' => "'Baloo 2', 'Arial Rounded MT Bold', sans-serif",
                    'font_weight' => '800',
                    'font_style' => 'normal',
                    'color' => '#203060',
                    'stroke_color' => '',
                    'stroke_width' => 0,
                    'align' => 'center',
                    'prefix' => 'Lomba ',
                    'enabled' => true,
                ],
                'group' => [
                    'label' => 'Kelompok / TPQ',
                    'x' => 50,
                    'y' => 63.0,
                    'font_size' => 18,
                    'font_family' => "'Baloo 2', 'Arial', sans-serif",
                    'font_weight' => '700',
                    'font_style' => 'normal',
                    'color' => '#203060',
                    'stroke_color' => '',
                    'stroke_width' => 0,
                    'align' => 'center',
                    'prefix' => 'Utusan : Kelompok / TPQ ',
                    'enabled' => false,
                ],
                'number' => [
                    'label' => 'Nomor Sertifikat',
                    'x' => 50,
                    'y' => 30.0,
                    'font_size' => 14,
                    'font_family' => "'Baloo 2', 'Arial', sans-serif",
                    'font_weight' => '700',
                    'font_style' => 'normal',
                    'color' => '#64748b',
                    'stroke_color' => '',
                    'stroke_width' => 0,
                    'align' => 'center',
                    'prefix' => 'No: ',
                    'enabled' => false,
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

        // Deep merge fields and normalize types so JS Alpine state receives clean booleans and numbers
        $fields = $defaults['fields'];
        if (isset($saved['fields']) && is_array($saved['fields'])) {
            foreach ($saved['fields'] as $key => $val) {
                $merged = isset($fields[$key]) ? array_merge($fields[$key], $val) : $val;
                $merged['x'] = (float) ($merged['x'] ?? 50);
                $merged['y'] = (float) ($merged['y'] ?? 50);
                $merged['font_size'] = (int) ($merged['font_size'] ?? 24);
                $merged['stroke_width'] = (float) ($merged['stroke_width'] ?? 0);
                $merged['stroke_color'] = (string) ($merged['stroke_color'] ?? '');
                $merged['font_style'] = (string) ($merged['font_style'] ?? 'normal');
                $merged['enabled'] = filter_var($merged['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN);
                $fields[$key] = $merged;
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
