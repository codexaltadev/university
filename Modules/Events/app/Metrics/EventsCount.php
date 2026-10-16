<?php

namespace Modules\Events\Metrics;

use Modules\Events\Models\Event;
use Platform\Statistics\MetricProvider;
use Platform\Statistics\StatContext;

/** Statistics counter "Events": the visible events of a place and those below it. */
class EventsCount implements MetricProvider
{
    public function key(): string
    {
        return 'events.count';
    }

    public function label(): string
    {
        return __('events::events.plural');
    }

    public function count(StatContext $ctx, array $params): int
    {
        return Event::query()->frontendVisible()->inContext($ctx->asSiteContext(), descendants: true)->count();
    }
}
