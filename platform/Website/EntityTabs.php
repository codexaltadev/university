<?php

namespace Platform\Website;

use Illuminate\Database\Eloquent\Model;
use Platform\Registry\Registry;
use Throwable;

/**
 * Tabs other modules registered for an entity page (FR-PROG-18). A tab that fails to
 * render is left out and reported, so the page itself never breaks (AC-PROG-02).
 */
class EntityTabs
{
    public function __construct(protected Registry $registry) {}

    /** @return list<EntityTab> visible tabs, in order */
    public function for(Model $entity): array
    {
        return collect($this->registry->all('entityTab'))
            ->filter(fn (array $entry): bool => $entity instanceof $entry['target'] && class_exists($entry['class']))
            ->map(fn (array $entry): EntityTab => app($entry['class']))
            ->filter(fn (EntityTab $tab): bool => $tab->visible($entity))
            ->sortBy(fn (EntityTab $tab): int => $tab::sort())
            ->values()->all();
    }

    /** @return list<array{key: string, label: string, html: string}> */
    public function render(Model $entity): array
    {
        $out = [];

        foreach ($this->for($entity) as $tab) {
            try {
                $out[] = ['key' => $tab::key(), 'label' => $tab::label(), 'html' => view($tab::view(), ['entity' => $entity, ...$tab->data($entity)])->render()];
            } catch (Throwable $e) {
                report($e);
            }
        }

        return $out;
    }
}
