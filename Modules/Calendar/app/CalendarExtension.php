<?php

namespace Modules\Calendar;

use Modules\Calendar\Blocks\CalendarSessionsBlock;
use Modules\Calendar\Blocks\UpcomingDatesBlock;
use Modules\Calendar\Filament\CalendarPlugin;
use Modules\Calendar\Models\Calendar;
use Modules\Calendar\Models\CalendarEvent;
use Modules\Calendar\Sections\UpcomingDatesSection;
use Modules\Calendar\Tabs\ProgramCalendarTab;
use Platform\Contracts\ModuleExtension;
use Platform\Registry\Registrar;

/**
 * Everything this module adds to the platform (SAD Part A §7).
 * Each registration needs the matching key in module.json "provides".
 */
final class CalendarExtension implements ModuleExtension
{
    public function register(Registrar $r): void
    {
        $r->websitePage('calendar.index', label: 'calendar::calendar.plural', path: '/'.Calendar::PATH);   // Menus › Module page
        $r->filamentPlugin(CalendarPlugin::class);                                           // Academic › Academic Calendar
        $r->searchable(Calendar::class, label: 'calendar::calendar.plural', weight: 6);       // website search (FR-SRCH-01)
        $r->sitemap(Calendar::class);                                                        // sitemap.xml (FR-SEO-02)
        $r->menuItemType(Calendar::class);                                                   // menus can link a calendar
        $r->vocabulary(CalendarEvent::TYPE, label: 'Academic date type', multiple: false);   // Class, Exam, Holiday … with colour (FR-CAL-03)
        $r->vocabulary(CalendarEvent::AUDIENCE, label: 'Academic calendar audience');        // Undergraduate, Graduate
        $r->layouts('calendar.show', ['table', 'month', 'timeline']);                        // FR-CAL-04; first = default
        $r->homepageSection(UpcomingDatesSection::class);                                    // FR-CAL-07
        $r->pageBlock(UpcomingDatesBlock::class);                                            // the same on any page
        $r->pageBlock(CalendarSessionsBlock::class);                                         // the calendar card on any page (DIU registrar-office)

        $program = 'Modules\\Program\\Models\\Program';
        if (class_exists($program)) {
            $r->entityTab($program, ProgramCalendarTab::class);   // FR-CAL-09; Program is optional
        }
    }
}
