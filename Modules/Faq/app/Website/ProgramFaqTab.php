<?php

namespace Modules\Faq\Website;

use Illuminate\Database\Eloquent\Model;
use Modules\Faq\Models\Faq;
use Platform\Relations\RelationEngine;
use Platform\Website\EntityTab;

/** "FAQ" on a program's page (FR-FAQ-03): the questions shown in that program; no tab without any. */
class ProgramFaqTab extends EntityTab
{
    public static function key(): string
    {
        return 'faq.program';
    }

    public static function label(): string
    {
        return __('faq::faq.title');
    }

    public static function view(): string
    {
        return 'faq::tabs.program';
    }

    public static function sort(): int
    {
        return 80;
    }

    public function visible(Model $entity): bool
    {
        return $this->query($entity)->exists();
    }

    public function data(Model $entity): array
    {
        return ['faqs' => $this->query($entity)->ordered()->get()];
    }

    protected function query(Model $entity)
    {
        return app(RelationEngine::class)->for($entity->getMorphClass().':'.$entity->getKey())->content(Faq::class)->query();
    }
}
