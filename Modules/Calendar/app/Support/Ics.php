<?php

namespace Modules\Calendar\Support;

use Illuminate\Support\Collection;
use Modules\Calendar\Models\Calendar;
use Modules\Calendar\Models\CalendarEvent;

/**
 * iCalendar export (FR-CAL-06, RFC 5545): one all-day VEVENT per dated date — DTEND is the
 * day after the last day (exclusive), so Google Calendar shows the right days (AC-CAL-02).
 * Dates known only in words are left out.
 */
class Ics
{
    /** @param Collection<int, CalendarEvent> $events */
    public static function build(Calendar $calendar, Collection $events, string $locale): string
    {
        $host = parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'localhost';
        $stamp = now('UTC')->format('Ymd\THis\Z');

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//'.$host.'//Academic Calendar//EN',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'X-WR-CALNAME:'.static::text($calendar->getTranslation('title', $locale, true)),
        ];

        foreach ($events->filter(fn (CalendarEvent $e): bool => $e->starts_on !== null) as $event) {
            $lines[] = 'BEGIN:VEVENT';
            $lines[] = 'UID:calendar-event-'.$event->getKey().'@'.$host;
            $lines[] = 'DTSTAMP:'.$stamp;
            $lines[] = 'DTSTART;VALUE=DATE:'.$event->starts_on->format('Ymd');
            $lines[] = 'DTEND;VALUE=DATE:'.$event->lastDay()->copy()->addDay()->format('Ymd');
            $lines[] = 'SUMMARY:'.static::text($event->getTranslation('title', $locale, true));
            if ($type = $event->type?->getTranslation('name', $locale, true)) {
                $lines[] = 'CATEGORIES:'.static::text($type);
            }
            $lines[] = 'END:VEVENT';
        }
        $lines[] = 'END:VCALENDAR';

        return implode("\r\n", array_map(static::fold(...), $lines))."\r\n";
    }

    /** Escape a TEXT value (RFC 5545 §3.3.11). */
    protected static function text(string $value): string
    {
        return str_replace(['\\', ';', ',', "\r\n", "\n"], ['\\\\', '\\;', '\\,', '\\n', '\\n'], trim($value));
    }

    /** Lines longer than 75 octets continue on the next line after a space (§3.1), never splitting a character. */
    protected static function fold(string $line): string
    {
        $out = '';
        $length = 0;
        foreach (mb_str_split($line) as $char) {
            $bytes = strlen($char);
            if ($length + $bytes > 75) {
                $out .= "\r\n ";
                $length = 1;
            }
            $out .= $char;
            $length += $bytes;
        }

        return $out;
    }
}
