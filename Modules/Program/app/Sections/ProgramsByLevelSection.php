<?php

namespace Modules\Program\Sections;

use Filament\Forms\Components\TextInput;
use Illuminate\Support\Collection;
use Modules\Program\Models\Program;
use Platform\Homepage\Section;
use Platform\Homepage\SectionContext;
use Platform\Statistics\StatContext;
use Platform\Taxonomy\Term;
use Platform\Taxonomy\Vocabularies;

/**
 * Homepage section "Programs by Level" (FR-PROG-16): one group per level in the
 * level order (Undergraduate, Graduate …); empty levels are left out.
 */
class ProgramsByLevelSection extends Section
{
    public static function key(): string
    {
        return 'program.by-level';
    }

    public static function label(): string
    {
        return __('program::program.sections.by_level');
    }

    public static function view(): string
    {
        return 'program::sections.by-level';
    }

    public static function settingsSchema(): array
    {
        return [
            TextInput::make('limit')->label(__('program::program.fields.section_per_level'))->numeric()->minValue(1)->maxValue(48)->placeholder('12'),
        ];
    }

    /** @return Collection<int, array{level: Term, programs: Collection<int, Program>}> */
    public function items(SectionContext $ctx): Collection
    {
        $within = StatContext::of($ctx->ownerKey)->idsOf((new Program)->getMorphClass());
        $limit = (int) ($ctx->setting('limit') ?: 12);

        $programs = Program::query()->listed()->when($within !== null, fn ($q) => $q->whereKey($within))->get()->groupBy('level_term_id');

        return Term::query()->where('vocabulary_id', app(Vocabularies::class)->find('program.level')->id)
            ->where('is_active', true)->ordered()->get()
            ->map(fn (Term $level): array => ['level' => $level, 'programs' => ($programs[$level->id] ?? collect())->take($limit)->values()])
            ->filter(fn (array $group): bool => $group['programs']->isNotEmpty())
            ->values();
    }
}
