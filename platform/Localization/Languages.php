<?php

namespace Platform\Localization;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Spatie\Translatable\Translatable;

/**
 * Website languages for engines and themes (C-15). Read from core_languages and
 * cached; before the table exists or has rows (fresh install, tests) the
 * languages come from config('platform.locales'), the first being the default.
 *
 * Each language: ['code', 'name', 'native_name', 'direction', 'is_default', 'is_active', 'use_native_digits'].
 */
class Languages
{
    public const CACHE_KEY = 'platform:languages';

    /** English and native names of languages a university is likely to add. */
    public const KNOWN = [
        'en' => ['English', 'English'],
        'bn' => ['Bangla', 'বাংলা'],
        'ar' => ['Arabic', 'العربية'],
        'zh' => ['Chinese', '中文'],
        'fr' => ['French', 'Français'],
        'hi' => ['Hindi', 'हिन्दी'],
        'ja' => ['Japanese', '日本語'],
        'ko' => ['Korean', '한국어'],
    ];

    /** @var list<array<string, mixed>>|null */
    protected ?array $languages = null;

    /** @return list<array<string, mixed>> every language, default first, then by sort order */
    public function all(): array
    {
        return $this->languages ??= $this->load();
    }

    /** @return list<array<string, mixed>> the languages the website offers */
    public function active(): array
    {
        return array_values(array_filter($this->all(), fn (array $l): bool => $l['is_active']));
    }

    /** @return list<string> every code, inactive included: their translations are kept (BRL-LANG-02) */
    public function codes(): array
    {
        return array_column($this->all(), 'code');
    }

    /** @return list<string> */
    public function activeCodes(): array
    {
        return array_column($this->active(), 'code');
    }

    public function default(): string
    {
        foreach ($this->all() as $language) {
            if ($language['is_default']) {
                return $language['code'];
            }
        }

        return $this->all()[0]['code'] ?? config('app.locale');
    }

    /** @return array<string, mixed>|null */
    public function find(string $code): ?array
    {
        foreach ($this->all() as $language) {
            if ($language['code'] === $code) {
                return $language;
            }
        }

        return null;
    }

    public function isActive(string $code): bool
    {
        return (bool) ($this->find($code)['is_active'] ?? false);
    }

    /** @return array<string, string> code => native name, for language tabs and switchers */
    public function labels(bool $activeOnly = true): array
    {
        return array_column($activeOnly ? $this->active() : $this->all(), 'native_name', 'code');
    }

    public function flush(): void
    {
        $this->languages = null;
        Cache::forget(self::CACHE_KEY);
        $this->applyFallback();
    }

    /**
     * Missing translations fall back to the default language (FR-LANG-05, FR-GEN-42):
     * for interface strings (translator) and for translatable fields (spatie).
     */
    public function applyFallback(): void
    {
        $default = $this->default();

        config(['app.fallback_locale' => $default]);
        app('translator')->setFallback($default);
        app(Translatable::class)->fallback(fallbackLocale: $default);
    }

    /** @return list<array<string, mixed>> */
    protected function load(): array
    {
        try {
            $rows = Cache::rememberForever(self::CACHE_KEY, fn (): array => Language::query()
                ->orderByDesc('is_default')->orderBy('sort_order')->orderBy('id')
                ->get(['code', 'name', 'native_name', 'direction', 'is_default', 'is_active', 'use_native_digits'])
                ->map(fn (Language $l): array => $l->only(['code', 'name', 'native_name', 'direction', 'is_default', 'is_active', 'use_native_digits']))
                ->all());
        } catch (QueryException) {
            $rows = [];   // table not migrated yet
        }

        return $rows !== [] ? $rows : $this->fromConfig();
    }

    /** @return list<array<string, mixed>> */
    protected function fromConfig(): array
    {
        $codes = array_values(config('platform.locales') ?: [config('app.locale')]);

        return array_map(fn (string $code): array => [
            'code' => $code,
            'name' => self::KNOWN[$code][0] ?? strtoupper($code),
            'native_name' => self::KNOWN[$code][1] ?? strtoupper($code),
            'direction' => 'ltr',
            'is_default' => $code === $codes[0],
            'is_active' => true,
            'use_native_digits' => false,
        ], $codes);
    }
}
