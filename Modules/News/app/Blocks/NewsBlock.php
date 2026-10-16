<?php

namespace Modules\News\Blocks;

use BackedEnum;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Modules\News\Models\Post;
use Platform\Pages\PageBlock;
use Platform\Relations\RelationEngine;
use Platform\Taxonomy\Term;
use Platform\Taxonomy\Vocabularies;

/**
 * "News" on any page (Module Guide "Content module checklist"): posts picked by hand, or the
 * latest of some categories, or the latest — of the main site's published posts.
 */
final class NewsBlock extends PageBlock
{
    public static function name(): string
    {
        return 'news';
    }

    public static function label(): string
    {
        return __('news::news.plural');
    }

    public static function icon(): string|BackedEnum|null
    {
        return Heroicon::OutlinedNewspaper;
    }

    public static function view(): string
    {
        return 'news::blocks.news';
    }

    public static function schema(): array
    {
        return [
            Select::make('categories')->label(__('news::news.fields.category'))->multiple()
                ->options(fn (): array => app(Vocabularies::class)->options(Post::CATEGORY))
                ->helperText(__('news::news.block.categories_help')),
            Select::make('posts')->label(__('news::news.block.posts'))->multiple()->searchable()
                ->getSearchResultsUsing(fn (string $search): array => Post::query()->whereRaw('LOWER(CAST(title AS CHAR)) LIKE ?', ['%'.mb_strtolower($search).'%'])
                    ->newest()->limit(30)->get()->mapWithKeys(fn (Post $p): array => [$p->id => $p->title])->all())
                ->getOptionLabelsUsing(fn (array $values): array => Post::query()->whereKey($values)->get()->mapWithKeys(fn (Post $p): array => [$p->id => $p->title])->all())
                ->helperText(__('news::news.block.posts_help')),
            Radio::make('layout')->label(__('news::news.section.display'))->inline()->default('grid')
                ->options(['grid' => __('news::news.layouts.grid'), 'list' => __('news::news.layouts.list')]),
            TextInput::make('limit')->label(__('news::news.section.count'))->integer()->minValue(1)->maxValue(24)->default(6),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return Collection<int, Post>
     */
    public static function posts(array $data): Collection
    {
        $query = app(RelationEngine::class)->for(RelationEngine::SITE_KEY)->content(Post::class)->query();
        $limit = (int) ($data['limit'] ?? 0) ?: 6;
        $picked = array_map('intval', (array) ($data['posts'] ?? []));

        if ($picked !== []) {
            $found = $query->whereKey($picked)->get()->keyBy('id');

            return collect($picked)->map(fn (int $id) => $found->get($id))->filter()->take($limit)->values();
        }

        $categories = array_map('intval', (array) ($data['categories'] ?? []));
        if ($categories !== []) {
            $ids = Term::query()->whereKey($categories)->get()->flatMap(fn (Term $t): array => [$t->id, ...$t->descendantIds()])->unique()->all();
            $query->whereHas('terms', fn ($t) => $t->whereIn('core_terms.id', $ids));
        }

        return $query->newest()->limit($limit)->get();
    }
}
