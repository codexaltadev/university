<?php

namespace Modules\Events\Freshness;

use Illuminate\Database\Eloquent\Model;
use Modules\Events\Filament\Resources\Events\EventResource;
use Modules\Events\Models\Event;
use Platform\Notifications\FreshnessSource;

/** Content Update Reminder (FR-NOTI-10): an entity's events go stale when nothing new was added there for a while. */
class EventsFreshness extends FreshnessSource
{
    public static function module(): string
    {
        return 'events';
    }

    public static function model(): string
    {
        return Event::class;
    }

    public static function label(): string
    {
        return __('events::events.plural');
    }

    public function editUrl(Model $record): ?string
    {
        return EventResource::getUrl('edit', ['record' => $record]);
    }
}
