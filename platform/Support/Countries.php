<?php

namespace Platform\Support;

use Locale;
use ResourceBundle;

/**
 * The world's countries by ISO 3166 two-letter code, named in a language, from PHP's intl data —
 * for any module that records where someone is from (a testimonial's writer …) without needing
 * the International module. HOME is the university's own country.
 *
 *   Countries::options()        // ['AF' => 'Afghanistan', …] in the current language
 *   Countries::name('BD', 'bn') // 'বাংলাদেশ'
 */
final class Countries
{
    public const HOME = 'BD';

    /** @var array<string, array<string, string>> */
    private static array $cache = [];

    /** @return array<string, string> code => name, by name */
    public static function options(?string $locale = null): array
    {
        $locale ??= app()->getLocale();
        if (isset(self::$cache[$locale])) {
            return self::$cache[$locale];
        }
        $codes = [];
        if (class_exists(ResourceBundle::class) && ($bundle = ResourceBundle::create('en', 'ICUDATA-region')) && ($countries = $bundle['Countries'] ?? null)) {
            foreach ($countries as $code => $name) {
                if (preg_match('/^[A-Z]{2}$/', (string) $code) && ! in_array($code, ['ZZ', 'EU', 'EZ', 'UN', 'QO'], true)) {
                    $codes[] = (string) $code;
                }
            }
        }
        $options = [];
        foreach ($codes as $code) {
            $options[$code] = self::name($code, $locale);
        }
        asort($options, SORT_LOCALE_STRING);

        return self::$cache[$locale] = $options;
    }

    public static function name(?string $code, ?string $locale = null): ?string
    {
        if (blank($code)) {
            return null;
        }
        $code = strtoupper((string) $code);

        return class_exists(Locale::class) ? (Locale::getDisplayRegion('-'.$code, $locale ?? app()->getLocale()) ?: $code) : $code;
    }

    /** From abroad: a country other than the university's own */
    public static function isAbroad(?string $code): bool
    {
        return filled($code) && strtoupper((string) $code) !== self::HOME;
    }
}
