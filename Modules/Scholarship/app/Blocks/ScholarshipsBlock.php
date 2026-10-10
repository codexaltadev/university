<?php

namespace Modules\Scholarship\Blocks;

use BackedEnum;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Modules\Scholarship\Models\Scholarship;
use Platform\Pages\PageBlock;
use Platform\Taxonomy\Term;
use Platform\Taxonomy\Vocabularies;

/**
 * "Scholarships" on any page (FR-FEE-10, Module Guide "Content module checklist"): some
 * scholarships picked by hand, or those of one or more categories (with their sub-categories),
 * or all — as cards or an accordion, up to a number.
 */
final class ScholarshipsBlock extends PageBlock
{
    public static function name(): string
    {
        return 'scholarships';
    }

    public static function label(): string
    {
        return __('scholarship::scholarship.plural');
    }

    public static function icon(): string|BackedEnum|null
    {
        return Heroicon::OutlinedTrophy;
    }

    public static function view(): string
    {
        return 'scholarship::blocks.scholarships';
    }

    public static function schema(): array
    {
        return [
            Select::make('categories')->label(__('scholarship::scholarship.block.categories'))->multiple()
                ->options(fn (): array => app(Vocabularies::class)->options(Scholarship::CATEGORY))
                ->helperText(__('scholarship::scholarship.block.categories_help')),
            Select::make('scholarships')->label(__('scholarship::scholarship.block.scholarships'))->multiple()->searchable()
                ->options(fn (): array => Scholarship::query()->ordered()->get()->mapWithKeys(fn (Scholarship $s): array => [$s->id => $s->title])->all())
                ->helperText(__('scholarship::scholarship.block.scholarships_help')),
            Radio::make('layout')->label(__('scholarship::scholarship.block.layout'))->inline()->default('cards')
                ->options(['cards' => __('scholarship::scholarship.layouts.cards'), 'accordion' => __('scholarship::scholarship.layouts.accordion')]),
            TextInput::make('limit')->label(__('scholarship::scholarship.block.limit'))->integer()->minValue(1)->maxValue(100)
                ->helperText(__('scholarship::scholarship.block.limit_help')),
        ];
    }

    /**
     * What the block shows: the picked scholarships in the order picked, else the categories'
     * top-level scholarships, else all of them.
     *
     * @param  array<string, mixed>  $data
     * @return Collection<int, Scholarship>
     */
    public static function scholarships(array $data): Collection
    {
        $picked = array_map('intval', (array) ($data['scholarships'] ?? []));
        $query = Scholarship::query()->frontendVisible()->withCount(['parts' => fn ($q) => $q->frontendVisible()]);

        if ($picked !== []) {
            $found = $query->whereKey($picked)->get()->keyBy('id');
            $list = collect($picked)->map(fn (int $id) => $found->get($id))->filter()->values();
        } else {
            $categories = array_map('intval', (array) ($data['categories'] ?? []));
            if ($categories !== []) {
                $ids = Term::query()->whereKey($categories)->get()->flatMap(fn (Term $t): array => [$t->id, ...$t->descendantIds()])->unique()->all();
                $query->where(fn ($q) => collect($ids)->each(fn (int $id) => $q->orWhere(fn ($w) => $w->withTerm($id))));
            }
            $list = $query->topLevel()->ordered()->get();
        }

        return filled($data['limit'] ?? null) ? $list->take((int) $data['limit'])->values() : $list;
    }
}
