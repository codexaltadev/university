<?php

namespace Modules\News\Sections;

use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Collection;
use Modules\News\Models\Post;
use Platform\Homepage\Section;
use Platform\Homepage\SectionContext;
use Platform\Relations\RelationEngine;

/**
 * Homepage section "Featured News" (FR-NEWS-04): the posts highlighted in this place, in highlight
 * order, as a slider or a grid. A highlight past its "until" date drops out by itself
 * (AC-NEWS-04); the post stays in /news.
 */
class FeaturedNewsSection extends Section
{
    public static function key(): string
    {
        return 'news.featured';
    }

    public static function label(): string
    {
        return __('news::news.section.featured');
    }

    public static function view(): string
    {
        return 'news::sections.list';
    }

    public static function settingsSchema(): array
    {
        return [
            TextInput::make('count')->label(__('news::news.section.count'))->integer()->minValue(1)->maxValue(12)->default(5),
            Radio::make('display')->label(__('news::news.section.display'))->inline()->default('slider')
                ->options(['slider' => __('news::news.layouts.slider'), 'grid' => __('news::news.layouts.grid')]),
        ];
    }

    /** @return Collection<int, Post> */
    public function items(SectionContext $ctx): Collection
    {
        return app(RelationEngine::class)->for($ctx->ownerKey)->content(Post::class)->highlighted(max(1, (int) ($ctx->setting('count') ?: 5)));
    }
}
