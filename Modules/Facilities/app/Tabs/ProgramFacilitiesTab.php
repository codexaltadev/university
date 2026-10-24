<?php

namespace Modules\Facilities\Tabs;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Facilities\Support\FacilitiesWebsite;
use Platform\Website\EntityTab;

/** "Facilities" on a program's page (FR-FCL-05): the facilities shown in it; no tab without any. */
class ProgramFacilitiesTab extends EntityTab
{
    public static function key(): string
    {
        return 'facilities.program';
    }

    public static function label(): string
    {
        return __('facilities::facilities.plural');
    }

    public static function view(): string
    {
        return 'facilities::tabs.program';
    }

    public static function sort(): int
    {
        return 50;
    }

    public function visible(Model $entity): bool
    {
        return $this->query($entity)->exists();
    }

    public function data(Model $entity): array
    {
        return ['items' => $this->query($entity)->get()];
    }

    protected function query(Model $entity): Builder
    {
        return FacilitiesWebsite::shown($entity->getMorphClass().':'.$entity->getKey());
    }
}
