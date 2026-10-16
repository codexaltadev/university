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
        ]);
    }

    /** @return list<string> every stop once, A–Z, for the search list */
    public static function allStops(array $timetable): array
    {
        return collect($timetable['groups'])->flatten(1)->pluck('stops')->flatten()->unique()->sort(SORT_NATURAL | SORT_FLAG_CASE)->values()->all();
    }
}
