<?php

namespace Modules\International\Sections;

use Illuminate\Support\Collection;
use Modules\International\Models\Visitor;
use Platform\Homepage\Section;
use Platform\Homepage\SectionContext;

/**
 * Homepage section "Distinguished Visitors" (FR-INTL-06): the guests chosen for the homepage
 * (highlighted) — DIU's homepage shows only those (owner 2026-10-24); the builder may ask for
 * more. A visitor may be from Bangladesh or abroad.
 */
class VisitorsSection extends Section
{
    public static function key(): string
    {
        return 'international.visitors';
    }

    public static function label(): string
    {
        return __('international::international.visitors_section');
    }

    public static function view(): string
    {
        return 'international::sections.visitors';
    }

    public static function model(): ?string
    {
        return Visitor::class;
    }

    public static function defaultSourceMode(): string
    {
        return 'highlighted';
    }

    /** @return Collection<int, Visitor> */
    public function items(SectionContext $ctx): Collection
    {
        return $ctx->applySourceMode(Visitor::class, 10, static::defaultSourceMode())->load('country');
    }
}
