<?php

namespace Modules\Transport\Sections;

use Modules\Transport\Support\Timetable;
use Modules\Transport\Support\TransportWebsite;
use Platform\Homepage\Section;
use Platform\Homepage\SectionContext;

/** Homepage section "Find your bus" (FR-TRN-06): pick a stop, see the routes through it; link to /transport. */
class TransportFinderSection extends Section
{
    public static function key(): string
    {
        return 'transport.finder';
    }

    public static function label(): string
    {
        return __('transport::transport.section.label');
    }

    public static function view(): string
    {
        return 'transport::sections.finder';
    }

    /** @return array{timetable: array, stops: list<string>} */
    public function items(SectionContext $ctx): array
    {
        $timetable = app(Timetable::class)->website();

        return ['timetable' => $timetable, 'stops' => TransportWebsite::allStops($timetable)];
    }
}
