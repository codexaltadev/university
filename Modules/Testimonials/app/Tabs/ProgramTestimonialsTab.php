<?php

namespace Modules\Testimonials\Tabs;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Testimonials\Support\TestimonialsWebsite;
use Platform\Website\EntityTab;

/** "Testimonials" on a program's page (DB-05 §6): what its students and alumni say; no tab without any. */
class ProgramTestimonialsTab extends EntityTab
{
    public static function key(): string
    {
        return 'testimonials.program';
    }

    public static function label(): string
    {
        return __('testimonials::testimonials.plural');
    }

    public static function view(): string
    {
        return 'testimonials::tabs.program';
    }

    public static function sort(): int
    {
        return 70;
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
        return TestimonialsWebsite::shown($entity->getMorphClass().':'.$entity->getKey());
    }
}
