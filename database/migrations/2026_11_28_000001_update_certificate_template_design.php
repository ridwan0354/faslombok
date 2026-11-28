<?php

use App\Models\EventSetting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $defaults = EventSetting::defaultCertificateTemplate();

        EventSetting::query()->each(function (EventSetting $setting) use ($defaults) {
            $config = $setting->config_json ?? [];
            $config['certificate_template'] = $defaults;
            $setting->config_json = $config;
            $setting->save();
        });
    }

    public function down(): void
    {
        // Keep current certificate template intact
    }
};
