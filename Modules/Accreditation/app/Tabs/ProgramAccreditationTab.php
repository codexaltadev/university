<?php

namespace Modules\Accreditation\Tabs;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Accreditation\Support\AccreditationWebsite;
use Platform\Website\EntityTab;

/** "Accreditation" on a program's page (FR-ACR-05): the accreditations shown in it; no tab without any. */
class ProgramAccreditationTab extends EntityTab
{
    public static function key(): string
    {
        return 'accreditation.program';
    }

    public static function label(): string
    {
        return __('accreditation::accreditation.tab');
    }

    public static function view(): string
    {
        return 'accreditation::tabs.program';
    }

    public static function sort(): int
    {
        return 60;
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
        return AccreditationWebsite::accreditations($entity->getMorphClass().':'.$entity->getKey());
    }
}
