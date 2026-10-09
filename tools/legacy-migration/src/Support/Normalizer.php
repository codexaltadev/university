<?php

namespace Legacy\Migration\Support;

use Illuminate\Support\Str;

/** Cleaning of legacy values (Data Migration Plan §4 "Normalizer"). */
class Normalizer
{
    /** Where the old system's files live (Plan §3); S03 imports them into the Media Library later. */
    public const FILE_BASE = 'https://webbackend.daffodilvarsity.edu.bd/';

    /** Trimmed text, or null when empty. */
    public static function text(?string $value): ?string
    {
        $value = $value === null ? null : trim(html_entity_decode($value, ENT_QUOTES | ENT_HTML5));

        return $value === '' ? null : $value;
    }

    /** Rich text, or null when it holds no visible text. */
    public static function html(?string $value): ?string
    {
        if ($value === null || trim(strip_tags(str_replace('&nbsp;', ' ', $value))) === '') {
            return null;
        }

        return trim($value);
    }

    /** {"en": "…"} for a translatable column; legacy content is English. */
    public static function en(?string $value): ?array
    {
        return $value === null ? null : ['en' => $value];
    }

    public static function shortName(?string $value): ?string
    {
        $value = static::text($value);

        return $value === null ? null : Str::upper($value);
    }

    public static function url(?string $value): ?string
    {
        $value = static::text($value);

        return $value !== null && filter_var($value, FILTER_VALIDATE_URL) ? $value : null;
    }

    /** A stored file path as a public link on the old file server. */
    public static function fileUrl(?string $path): ?string
    {
        $path = static::text($path);

        return $path === null ? null : (str_starts_with($path, 'http') ? $path : self::FILE_BASE.ltrim($path, '/'));
    }

    /**
     * Credits, years and semesters from the free text "program_credit_duration"
     * (DB-03 §15: parse + manual check), e.g. "Credit-154 … Duration -04 Years",
     * "a total of 194 credit hours … five (5) academic years", "12 semesters".
     *
     * @return array{credits: ?float, years: ?float, terms: ?int}
     */
    public static function creditsAndDuration(?string $html): array
    {
        $text = preg_replace('/\s+/', ' ', strip_tags(html_entity_decode((string) $html, ENT_QUOTES | ENT_HTML5)));

        $credits = preg_match('/(\d{2,3}(?:\.\d)?)\s*(?:credit|cr\.?\b)/i', $text, $m) || preg_match('/credits?\s*(?:hours?)?\s*[-:]?\s*(\d{2,3}(?:\.\d)?)/i', $text, $m)
            ? (float) $m[1] : null;
        // "4 Years", "04 Years", "4 (four) academic years", "five (5) academic years"
        $years = preg_match('/(\d(?:\.\d)?)\)?\s*(?:\([a-z]+\)\s*)?(?:academic\s+)?years?/i', $text, $m) ? (float) $m[1] : null;
        $terms = preg_match('/(\d{1,2})\s*(?:\([a-z]+\)\s*)?(?:semesters|trimesters)/i', $text, $m) ? (int) $m[1] : null;

        return ['credits' => $credits, 'years' => $years, 'terms' => $terms];
    }
}
