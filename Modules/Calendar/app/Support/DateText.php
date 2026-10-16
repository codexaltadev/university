<?php

namespace Modules\Calendar\Support;

use Illuminate\Support\Carbon;

/**
 * Dates written as words (DB-03 §12 v1.6, Data Migration Plan 7.6): "9 May 2026",
 * "9 May–15 May 2026", "30 December 2024-5 January 2025", "3 – 10 January 2026",
 * "January 9 – 18, 2025", "December 29— January 01, 2023", "26 June- July 01, 2023", "July 12, 2023". A missing year
 * comes from the calendar. Words after the date ("(2 Weeks) No. of classes …") are kept as the
 * rest; "1st week of March" is not a date.
 */
class DateText
{
    protected const MONTH = '(jan(?:uary)?|feb(?:ruary)?|mar(?:ch)?|apr(?:il)?|may|june?|july?|aug(?:ust)?|sep(?:t(?:ember)?)?|oct(?:ober)?|nov(?:ember)?|dec(?:ember)?)';

    /**
     * @return array{starts_on: Carbon, ends_on: ?Carbon, rest: string}|null
     */
    public static function parse(string $text, ?int $defaultYear = null): ?array
    {
        $s = trim(preg_replace('/\s+/u', ' ', str_replace(["\u{2013}", "\u{2014}", "\u{2212}"], '-', $text)));
        $s = preg_replace('/\s*-\s*/', '-', $s);
        $s = preg_replace('/(\d),\s*(\d{4})/', '$1 $2', $s);   // "18, 2025" / "06,2023"
        $M = self::MONTH;

        $patterns = [
            // 9 May-15 May 2026 · 30 December 2024-5 January 2025 · 12 July-15 August
            "/^(\d{1,2})(?:st|nd|rd|th)? {$M},?(?: (\d{4}))?-(\d{1,2})(?:st|nd|rd|th)? {$M},?(?: (\d{4}))?/i" => fn ($m) => [[$m[1], $m[2], $m[3] ?: null], [$m[4], $m[5], $m[6] ?? null]],
            // 26 June-July 01 2023
            "/^(\d{1,2})(?:st|nd|rd|th)? {$M},?(?: (\d{4}))?-{$M} (\d{1,2})(?:st|nd|rd|th)?(?: (\d{4}))?/i" => fn ($m) => [[$m[1], $m[2], $m[3] ?: null], [$m[5], $m[4], $m[6] ?? null]],
            // 3-10 January 2026
            "/^(\d{1,2})-(\d{1,2}) {$M},?(?: (\d{4}))?/i" => fn ($m) => [[$m[1], $m[3], null], [$m[2], $m[3], $m[4] ?? null]],
            // December 29-January 01 2023 · June 26 2023-July 01 2023
            "/^{$M} (\d{1,2})(?: (\d{4}))?-{$M} (\d{1,2})(?: (\d{4}))?/i" => fn ($m) => [[$m[2], $m[1], $m[3] ?: null], [$m[5], $m[4], $m[6] ?? null]],
            // January 9-18 2025
            "/^{$M} (\d{1,2})-(\d{1,2})(?: (\d{4}))?/i" => fn ($m) => [[$m[2], $m[1], null], [$m[3], $m[1], $m[4] ?? null]],
            // 9 May 2026
            "/^(\d{1,2})(?:st|nd|rd|th)? {$M},?(?: (\d{4}))?/i" => fn ($m) => [[$m[1], $m[2], $m[3] ?? null], null],
            // July 12 2023
            "/^{$M} (\d{1,2})(?:st|nd|rd|th)?(?: (\d{4}))?(?!\d)/i" => fn ($m) => [[$m[2], $m[1], $m[3] ?? null], null],
        ];

        foreach ($patterns as $pattern => $parts) {
            if (! preg_match($pattern, $s, $m)) {
                continue;
            }
            [$from, $to] = $parts($m);
            $endYear = (int) (($to[2] ?? null) ?: ($from[2] ?? null) ?: $defaultYear);
            if (! $endYear) {
                return null;
            }

            $end = $to ? static::date((int) $to[0], $to[1], $endYear) : null;
            $startYear = (int) ($from[2] ?: $endYear);
            $start = static::date((int) $from[0], $from[1], $startYear);
            if (! $start || ($to && ! $end)) {
                return null;
            }
            if ($end && ! $from[2] && $start->gt($end)) {
                $start = $start->subYear();   // "29 December-01 January 2023": the start is the year before
            }
            if ($end && $end->lt($start)) {
                return null;
            }

            return [
                'starts_on' => $start,
                'ends_on' => $end && ! $end->eq($start) ? $end : null,
                'rest' => trim(substr($s, strlen($m[0])), " ,.-\t"),
            ];
        }

        return null;
    }

    protected static function date(int $day, string $month, int $year): ?Carbon
    {
        $number = (int) date('n', strtotime('1 '.substr($month, 0, 3).' 2000'));
        if (! checkdate($number, $day, $year)) {
            return null;
        }

        return Carbon::create($year, $number, $day)->startOfDay();
    }
}
