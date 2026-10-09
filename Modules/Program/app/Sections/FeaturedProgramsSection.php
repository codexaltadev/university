<?php

namespace Modules\Program\Sections;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Collection;
use Modules\Program\Models\Program;
use Platform\Homepage\Section;
use Platform\Homepage\SectionContext;
use Platform\Statistics\StatContext;

/**
 * Homepage section "Featured Programs" (FR-PROG-16): the programs picked here in that
 * order, or else the first listed ones. On a Faculty / Department microsite only its own.
 */
class FeaturedProgramsSection extends Section
{
    public static function key(): string
    {
        return 'program.featured';
    }

    public static function label(): string
    {
        return __('program::program.sections.featured');
    }

    public static function view(): string
    {
        return 'program::sections.featured';
    }

    public static function settingsSchema(): array
    {
        return [
            Select::make('program_ids')->label(__('program::program.fields.section_programs'))->multiple()->searchable()
                ->options(fn (): array => Program::query()->ordered()->get()->mapWithKeys(fn (Program $p): array => [$p->id => "{$p->short_name} — {$p->name}"])->all())
                ->helperText(__('program::program.help.section_programs')),
            TextInput::make('limit')->label(__('program::program.fields.section_limit'))->numeric()->minValue(1)->maxValue(24)->placeholder('6'),
        ];
    }

    public function items(SectionContext $ctx): Collection
    {
        $within = StatContext::of($ctx->ownerKey)->idsOf((new Program)->getMorphClass());
        $picked = array_map('intval', (array) $ctx->setting('program_ids', []));
        $limit = (int) ($ctx->setting('limit') ?: 6);

        $query = Program::query()->listed()->with('level')->when($within !== null, fn ($q) => $q->whereKey($within));

        if ($picked === []) {
            return $query->limit($limit)->get();
        }

        return $query->whereKey($picked)->get()
            ->sortBy(fn (Program $p): int => array_search($p->id, $picked, true))
            ->take($limit)->values();
    }
}
