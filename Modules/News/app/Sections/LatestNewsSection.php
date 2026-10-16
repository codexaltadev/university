<?php

namespace Modules\News\Sections;

use Filament\Forms\Components\Radio;
use Illuminate\Support\Collection;
use Modules\News\Models\Post;
use Platform\Homepage\Section;
use Platform\Homepage\SectionContext;

/**
 * Homepage section "Latest News" (FR-NEWS-04): the place's posts in the builder's source mode
 * (latest, highlighted first, highlighted, picked — FR-HOME-11); the main homepage shows only
 * posts published on the main site (AC-NEWS-01).
 */
class LatestNewsSection extends Section
{
    public static function key(): string
    {
        return 'news.latest';
    }

    public static function label(): string
    {
        return __('news::news.section.latest');
    }

    public static function view(): string
    {
        return 'news::sections.list';
    }

    public static function model(): ?string
    {
        return Post::class;
    }

    public static function settingsSchema(): array
    {
        return [
            Radio::make('display')->label(__('news::news.section.display'))->inline()->default('grid')
                ->options(['grid' => __('news::news.layouts.grid'), 'list' => __('news::news.layouts.list')]),
        ];
    }

    /** @return Collection<int, Post> */
    public function items(SectionContext $ctx): Collection
    {
        return $ctx->applySourceMode(Post::class, defaultCount: 6);
    }
}
