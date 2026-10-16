<?php

namespace Modules\Transport\Support;

use Illuminate\Http\Response;
use Platform\Context\SiteContext;
use Platform\Seo\Seo;

/**
 * /transport (FR-TRN-06/07): the live transport system's routes — Regular / Shuttle / Friday,
 * stops, times to and from campus, days off, notes, map — with a stop search. Main site only
 * (transport belongs to the university, not to a department).
 */
class TransportWebsite
{
    public const PATH = 'transport';

    public function __construct(protected TransportApi $api) {}

    public function respond(string $rest, SiteContext $context, string $locale): ?Response
    {
        if (trim($rest, '/') !== self::PATH || $context->isMicrosite()) {
            return null;
        }

        $timetable = $this->api->timetable();
        app(Seo::class)->set(['title' => __('transport::transport.title'), 'path' => '/'.self::PATH,
            'description' => $timetable['semester'] ? __('transport::transport.description', ['semester' => $timetable['semester']]) : null]);

        $group = request()->query('service');

        return response()->view('transport::index', [
            'timetable' => $timetable,
            'group' => is_string($group) && isset($timetable['groups'][$group]) ? $group : array_key_first($timetable['groups']),
            'stops' => self::allStops($timetable),
            'page' => self::page(),
        ]);
    }

    /** The page's own text from Settings › Transport (not in the API), in the current language. */
    public static function page(): array
    {
        $text = fn (string $key): ?string => ($v = settings()->translate(settings('transport.'.$key))) && is_string($v) && trim($v) !== '' ? trim($v) : null;

        return [
            'intro' => $text('intro'),
            'pass_title' => $text('pass_title'),
            'pass_subtitle' => $text('pass_subtitle'),
            'pass_how' => $text('pass_how'),
            'pass_why' => $text('pass_why'),
            'pass_image' => settings()->media('transport.pass_image'),
            'fees' => array_values(array_filter((array) settings('transport.fees'), fn ($f): bool => is_array($f) && filled($f['route'] ?? null))),
            'fees_note' => $text('fees_note'),
        ];
    }

    /** @return list<string> every stop once, A–Z, for the search list */
    public static function allStops(array $timetable): array
    {
        return collect($timetable['groups'])->flatten(1)->pluck('stops')->flatten()->unique()->sort(SORT_NATURAL | SORT_FLAG_CASE)->values()->all();
    }
}
