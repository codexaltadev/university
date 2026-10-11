<?php

namespace Modules\Admission\Blocks;

use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Modules\Admission\Models\AdmissionHighlight;
use Platform\Pages\PageBlock;
use Platform\Taxonomy\Term;
use Platform\Taxonomy\Vocabularies;

/** Admission information cards — all, or some categories; grouped by category or one grid (FR-ADM-15). */
final class HighlightsBlock extends PageBlock
{
    public static function name(): string
    {
        return 'admission-highlights';
    }

    public static function label(): string
    {
        return __('admission::admission.blocks.highlights');
    }

    public static function icon(): string|BackedEnum|null
    {
        return Heroicon::OutlinedSquares2x2;
    }

    public static function view(): string
    {
        return 'admission::blocks.highlights';
    }

    public static function schema(): array
    {
        return [
            Select::make('categories')->label(__('admission::admission.block.categories'))->multiple()
                ->options(fn (): array => app(Vocabularies::class)->options(AdmissionHighlight::CATEGORY))
                ->helperText(__('admission::admission.block.categories_help')),
            Toggle::make('grouped')->label(__('admission::admission.block.grouped'))->default(true),
        ];
    }

    /**
     * The cards to show: of the picked categories (all when none), in the vocabulary's order
     * when grouped — cards without a category last — else one list.
     *
     * @param  array<string, mixed>  $data
     * @return Collection<int, array{category: ?Term, items: Collection<int, AdmissionHighlight>}>
     */
    public static function groups(array $data): Collection
    {
        $items = AdmissionHighlight::query()->frontendVisible()->with('terms')->ordered()->get();
        $termOf = fn (AdmissionHighlight $h): ?int => $h->termIdsIn(AdmissionHighlight::CATEGORY)[0] ?? null;

        $picked = array_map('intval', (array) ($data['categories'] ?? []));
        if ($picked !== []) {
            $items = $items->filter(fn (AdmissionHighlight $h): bool => in_array($termOf($h), $picked, true))->values();
        }

        if (! ($data['grouped'] ?? true)) {
            return collect([['category' => null, 'items' => $items]]);
        }

        $order = array_keys(app(Vocabularies::class)->options(AdmissionHighlight::CATEGORY));
        $terms = Term::query()->whereKey($order)->get()->sortBy(fn (Term $t): int => array_search($t->id, $order, true));

        return $terms->map(fn (Term $t): array => ['category' => $t, 'items' => $items->filter(fn (AdmissionHighlight $h): bool => $termOf($h) === $t->id)->values()])
            ->push(['category' => null, 'items' => $items->filter(fn (AdmissionHighlight $h): bool => $termOf($h) === null)->values()])
            ->filter(fn (array $g): bool => $g['items']->isNotEmpty())->values();
    }
}
