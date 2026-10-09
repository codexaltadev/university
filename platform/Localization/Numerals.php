<?php

namespace Platform\Localization;

/**
 * Numbers in the language's own digits when the language asks for it
 * (core_languages.use_native_digits, FR-LANG-08). Admin always uses English digits (UI/UX Part A §9).
 */
class Numerals
{
    /** @var array<string, list<string>> */
    protected const DIGITS = [
        'bn' => ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'],
        'ar' => ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'],
        'hi' => ['०', '१', '२', '३', '४', '५', '६', '७', '८', '९'],
    ];

    public static function localize(string|int|float $value, ?string $locale = null): string
    {
        $locale ??= app()->getLocale();
        $value = (string) $value;

        if (! isset(self::DIGITS[$locale]) || ! (app(Languages::class)->find($locale)['use_native_digits'] ?? false)) {
            return $value;
        }

        return strtr($value, array_combine(range(0, 9), self::DIGITS[$locale]));
    }
}
