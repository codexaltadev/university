<?php

namespace Modules\Links\Sections;

use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Collection;
use Modules\Links\Models\Link;
use Modules\Links\Support\LinksWebsite;
use Platform\Homepage\Section;
use Platform\Homepage\SectionContext;
use Platform\Taxonomy\Vocabularies;

/** Homepage section "Important Links" (FR-LINK-02): the place's links in their order, one group if chosen; tiles or a list. */
class ImportantLinksSection extends Section
{
    public static function key(): string
    {
        return 'links.list';
    }

    public static function label(): string
    {
        return __('links::links.section.label');
    }

    public static function view(): string
    {
        return 'links::sections.list';
    }

    public static function settingsSchema(): array
    {
        return [
            Select::make('group')->label(__('links::links.fields.group'))->options(fn (): array => app(Vocabularies::class)->options(Link::GROUP))
                ->placeholder(__('links::links.all')),
            Radio::make('display')->label(__('links::links.section.display'))->inline()->default('tiles')
                ->options(['tiles' => __('links::links.layouts.tiles'), 'list' => __('links::links.layouts.list')]),
            TextInput::make('count')->label(__('links::links.section.count'))->integer()->minValue(1)->maxValue(48)->default(12),
        ];
    }

    /** @return Collection<int, Link> */
    public function items(SectionContext $ctx): Collection
    {
        return self::pick($ctx->ownerKey, $ctx->setting('group'), (int) ($ctx->setting('count') ?: 12));
    }

    /** @return Collection<int, Link> */
    public static function pick(string $contextKey, mixed $group, int $count): Collection
    {
        return LinksWebsite::shown($contextKey)->when($group, fn ($query) => $query->withTerm((int) $group))->limit(max(1, $count))->get();
    }
}
