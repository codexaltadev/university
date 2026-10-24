<?php

namespace Modules\Events;

use Modules\Events\Filament\EventsPlugin;
use Modules\Events\Freshness\EventsFreshness;
use Modules\Events\Metrics\EventsCount;
use Modules\Events\Models\Event;
use Modules\Events\Sections\PastEventsSection;
use Modules\Events\Sections\UpcomingEventsSection;
use Modules\Events\Settings\EventsSettings;
use Platform\Contracts\ModuleExtension;
use Platform\Registry\Registrar;

/**
 * Everything this module adds to the platform (SAD Part A §7).
 * Each registration needs the matching key in module.json "provides".
 */
final class EventsExtension implements ModuleExtension
{
    public function register(Registrar $r): void
    {
        $r->filamentPlugin(EventsPlugin::class);                                                  // Content › Events
        $r->attachable(Event::class);                                                             // "Show in", main site (Admin), highlight
        $r->assignable(Event::class, roles: Event::ROLES);                                        // speakers, moderators (FR-EVT-04)
        $r->vocabulary(Event::CATEGORY, label: 'Event category', hierarchical: true, multiple: false);
        $r->metric('events.count', EventsCount::class);
        $r->homepageSection(UpcomingEventsSection::class);                                        // FR-EVT-08
        $r->homepageSection(PastEventsSection::class);
        $r->freshnessSource(EventsFreshness::class);
        $r->websitePage('events.index', label: 'events::events.plural', path: '/'.Event::PATH);    // also /{entity}/events
        $r->searchable(Event::class, label: 'events::events.plural', weight: 6);
        $r->sitemap(Event::class);
        $r->menuItemType(Event::class);
        $r->layouts('events.listing', ['list', 'cards', 'timeline', 'calendar']);                 // FR-EVT-03; first = default
        $r->settings(EventsSettings::class);                                                      // the events pages' own text
    }
}
