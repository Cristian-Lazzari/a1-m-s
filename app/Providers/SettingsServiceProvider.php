<?php

namespace App\Providers;

use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class SettingsServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        try {
            if (!Schema::hasTable('settings')) {
                return;
            }

            $setting = Setting::query()->where('name', 'Lingua')->first();
        } catch (\Throwable $exception) {
            return;
        }

        if (!$setting) {
            return;
        }

        $data = json_decode($setting->property ?? '{}', true);
        $defaultLocale = is_array($data) ? (string) ($data['default'] ?? '') : '';

        if ($defaultLocale === '') {
            return;
        }

        App::setLocale($defaultLocale);
        Carbon::setLocale($defaultLocale);

        config([
            'configurazione.default_lang' => $defaultLocale,
        ]);
    }
}
