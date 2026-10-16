<?php

namespace Modules\Gallery\Tabs;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Gallery\Models\Album;
use Platform\Website\EntityTab;

/** "Photo albums" on an event's page (FR-GAL-06): the visible albums of that event; nothing without one. */
class EventAlbumsTab extends EntityTab
{
    public static function key(): string
    {
        return 'gallery.event';
    }

    public static function label(): string
    {
        return __('gallery::gallery.albums');
    }

    public static function view(): string
    {
        return 'gallery::tabs.event';
    }

    public static function sort(): int
    {
        return 40;
    }

    public function visible(Model $entity): bool
    {
        return $this->query($entity)->exists();
    }

    public function data(Model $entity): array
    {
        return ['albums' => $this->query($entity)->with('videos')->get()];
    }

    protected function query(Model $event): Builder
    {
        return Album::query()->frontendVisible()->where('event_id', $event->getKey())->newest();
    }
}
