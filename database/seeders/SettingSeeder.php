<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Support\Settings;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Store the default affiliate terms so they show up, editable, under
     * Admin → Settings. Everything else stays empty until the owner fills it in.
     */
    public function run(): void
    {
        $defaults = [
            'affiliates.default_commission_rate' => config('site.settings.affiliates.default_commission_rate'),
            'affiliates.attribution_days' => config('site.settings.affiliates.attribution_days'),
        ];

        $missing = array_diff_key($defaults, Setting::query()->pluck('key', 'key')->all());

        if ($missing !== []) {
            Settings::set($missing);
        }
    }
}
