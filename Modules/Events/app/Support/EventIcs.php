<?php

namespace Modules\Events\Support;

use Modules\Events\Models\Event;

/**
 * "Add to calendar" file (FR-EVT-07, RFC 5545): one VEVENT — all day as dates (the end is the
 * day after, exclusive), timed in UTC.
 */
class EventIcs
{
    public static function build(Event $event, string $locale): string
    {
        $host = parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'localhost';
        $text = fn (?string $v): string => str_replace(['\\', ';', ',', "\r\n", "\n"], ['\\\\', '\\;', '\\,', '\\n', '\\n'], trim((string) $v));

        if ($event->is_all_day) {
            $start = 'DTSTART;VALUE=DATE:'.$event->starts_at->format('Ymd');
            $end = 'DTEND;VALUE=DATE:'.($event->ends_at ?? $event->starts_at)->copy()->addDay()->format('Ymd');
        } else {
            $start = 'DTSTART:'.$event->starts_at->copy()->utc()->format('Ymd\THis\Z');
            $end = 'DTEND:'.($event->ends_at ?? $event->starts_at->copy()->addHour())->copy()->utc()->format('Ymd\THis\Z');
        }

        $lines = array_filter([
            'BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//'.$host.'//Events//EN', 'CALSCALE:GREGORIAN', 'METHOD:PUBLISH',
            'BEGIN:VEVENT',
            'UID:event-'.$event->getKey().'@'.$host,
            'DTSTAMP:'.now('UTC')->format('Ymd\THis\Z'),
            $start, $end,
            'SUMMARY:'.$text($event->getTranslation('title', $locale, true)),
            filled($venue = $event->getTranslation('venue', $locale, true)) ? 'LOCATION:'.$text($venue) : null,
            'URL:'.url($event->url($locale)),
            'END:VEVENT',
            'END:VCALENDAR',
        ]);

        return implode("\r\n", array_map(static function (string $line): string {
            $out = '';
            $length = 0;
            foreach (mb_str_split($line) as $char) {
                if ($length + strlen($char) > 75) {
                    $out .= "\r\n ";
                    $length = 1;
                }
                $out .= $char;
                $length += strlen($char);
            }

            return $out;
        }, $lines))."\r\n";
    }
}
