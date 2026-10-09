<?php

namespace Modules\Localization\Database\Seeders;

use Illuminate\Database\Seeder;
use Platform\Localization\Language;
use Platform\Localization\Languages;

/**
 * The starting website languages from config('platform.locales'), the first as
 * default (FR-LANG-01: English and Bangla). Safe to run twice: existing rows stay as they are.
 */
class LocalizationDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $hasDefault = Language::query()->where('is_default', true)->exists();

        foreach (array_values(config('platform.locales') ?: [config('app.locale')]) as $i => $code) {
            Language::query()->firstOrCreate(['code' => $code], [
                'name' => Languages::KNOWN[$code][0] ?? strtoupper($code),
                'native_name' => Languages::KNOWN[$code][1] ?? strtoupper($code),
                'is_default' => ! $hasDefault && $i === 0,
                'sort_order' => $i,
            ]);
        }
    }
}
