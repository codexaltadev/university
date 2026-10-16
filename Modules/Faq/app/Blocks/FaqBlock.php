<?php

namespace Modules\Faq\Blocks;

use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Modules\Faq\Models\Faq;
use Platform\Pages\PageBlock;
use Platform\Taxonomy\Term;
use Platform\Taxonomy\Vocabularies;

/**
 * "FAQ" on any page (FR-FAQ-03, Module Guide "Content module checklist"): questions picked
 * by hand, or of one or more categories (with sub-categories), or all — as an accordion.
 */
final class FaqBlock extends PageBlock
{
    public static function name(): string
    {
        return 'faq';
    }

    public static function label(): string
    {
        return __('faq::faq.title');
    }

    public static function icon(): string|BackedEnum|null
    {
        return Heroicon::OutlinedQuestionMarkCircle;
    }

    public static function view(): string
    {
        return 'faq::blocks.faq';
    }

    public static function schema(): array
    {
        return [
            Select::make('categories')->label(__('faq::faq.fields.category'))->multiple()
                ->options(fn (): array => app(Vocabularies::class)->options(Faq::CATEGORY))
                ->helperText(__('faq::faq.block.categories_help')),
            Select::make('faqs')->label(__('faq::faq.block.faqs'))->multiple()->searchable()
                ->getSearchResultsUsing(fn (string $search): array => Faq::query()->where('question', 'like', '%'.$search.'%')->limit(30)->get()->mapWithKeys(fn (Faq $f): array => [$f->id => $f->question])->all())
                ->getOptionLabelsUsing(fn (array $values): array => Faq::query()->whereKey($values)->get()->mapWithKeys(fn (Faq $f): array => [$f->id => $f->question])->all())
                ->helperText(__('faq::faq.block.faqs_help')),
            TextInput::make('limit')->label(__('faq::faq.block.limit'))->integer()->minValue(1)->maxValue(100)->default(10),
        ];
    }

    /**
     * The picked questions in the order picked, else the categories' (with sub-categories), else all.
     *
     * @param  array<string, mixed>  $data
     * @return Collection<int, Faq>
     */
    public static function faqs(array $data): Collection
    {
        $picked = array_map('intval', (array) ($data['faqs'] ?? []));
        $query = Faq::query()->frontendVisible();

        if ($picked !== []) {
            $found = $query->whereKey($picked)->get()->keyBy('id');
            $list = collect($picked)->map(fn (int $id) => $found->get($id))->filter()->values();
        } else {
            $categories = array_map('intval', (array) ($data['categories'] ?? []));
            if ($categories !== []) {
                $ids = Term::query()->whereKey($categories)->get()->flatMap(fn (Term $t): array => [$t->id, ...$t->descendantIds()])->unique()->all();
                $query->where(fn ($q) => collect($ids)->each(fn (int $id) => $q->orWhere(fn ($w) => $w->withTerm($id))));
            }
            $list = $query->ordered()->get();
        }

        return $list->take((int) ($data['limit'] ?? 10) ?: 10)->values();
    }
}
