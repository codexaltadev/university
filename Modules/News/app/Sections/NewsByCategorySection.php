<?php

namespace Modules\News\Sections;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Collection;
use Modules\News\Models\Post;
use Platform\Homepage\Section;
use Platform\Homepage\SectionContext;
use Platform\Relations\RelationEngine;
use Platform\Taxonomy\Term;
use Platform\Taxonomy\Vocabularies;

/**
 * Homepage section "News by Category" (FR-NEWS-04): for each chosen category (with its
 * sub-categories) the latest posts of this place, one column per category.
 */
class NewsByCategorySection extends Section
{
    public static function key(): string
    {
        return 'news.by-category';
    }

    public static function label(): string
    {
        return __('news::news.section.by_category');
    }

    public static function view(): string
    {
        return 'news::sections.by-category';
    }

    public static function settingsSchema(): array
    {
        return [
            Select::make('categories')->label(__('news::news.fields.category'))->multiple()->required()
                ->options(fn (): array => app(Vocabularies::class)->options(Post::CATEGORY)),
            TextInput::make('count')->label(__('news::news.section.per_category'))->integer()->minValue(1)->maxValue(10)->default(3),
        ];
    }

    /** @return Collection<int, array{category: Term, posts: Collection<int, Post>}> */
    public function items(SectionContext $ctx): Collection
    {
        $limit = max(1, (int) ($ctx->setting('count') ?: 3));
        $ids = array_map('intval', (array) $ctx->setting('categories', []));

        return Term::query()->whereKey($ids)->get()
            ->sortBy(fn (Term $t): int => (int) array_search($t->id, $ids, true))->values()
            ->map(fn (Term $category): array => [
                'category' => $category,
                'posts' => app(RelationEngine::class)->for($ctx->ownerKey)->content(Post::class)->query()
                    ->whereHas('terms', fn ($t) => $t->whereIn('core_terms.id', [$category->id, ...$category->descendantIds()]))
                    ->newest()->limit($limit)->get(),
            ])
            ->filter(fn (array $group): bool => $group['posts']->isNotEmpty())->values();
    }
}
