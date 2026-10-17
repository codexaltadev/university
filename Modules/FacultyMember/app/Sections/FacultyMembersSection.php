<?php

namespace Modules\FacultyMember\Sections;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Collection;
use Modules\FacultyMember\Models\FacultyMember;
use Modules\FacultyMember\Support\FacultyMemberWebsite;
use Platform\Homepage\Section;
use Platform\Homepage\SectionContext;
use Platform\Taxonomy\Vocabularies;

/**
 * Homepage section "Faculty Members" (FR-FM-05, FR-DEPT-07): teachers in designation order —
 * on a Department microsite its own, on a Faculty microsite its departments', on the main site
 * all; optionally only some designations (e.g. Professors).
 */
class FacultyMembersSection extends Section
{
    public static function key(): string
    {
        return 'faculty-member.list';
    }

    public static function label(): string
    {
        return __('facultymember::facultymember.plural');
    }

    public static function view(): string
    {
        return 'facultymember::sections.list';
    }

    public static function settingsSchema(): array
    {
        return [
            TextInput::make('count')->label(__('facultymember::facultymember.section.count'))->integer()->minValue(1)->maxValue(48)->default(8),
            Select::make('designations')->label(__('facultymember::facultymember.section.designations'))->multiple()
                ->options(fn (): array => app(Vocabularies::class)->options(FacultyMember::DESIGNATION))
                ->helperText(__('facultymember::facultymember.section.designations_help')),
        ];
    }

    /** @return array{members: Collection<int, FacultyMember>, url: string} plain values only: the homepage caches them */
    public function items(SectionContext $ctx): array
    {
        $site = $ctx->site();
        $departments = FacultyMemberWebsite::departmentsOf($site);
        if ($departments === []) {
            return ['members' => collect(), 'url' => ''];
        }

        return [
            'members' => FacultyMember::query()->frontendVisible()
                ->when($departments !== null, fn ($q) => $q->inDepartments($departments))
                ->when(array_filter((array) $ctx->setting('designations')), fn ($q, $ids) => $q->whereIn('designation_term_id', $ids))
                ->directoryOrder()->limit((int) ($ctx->setting('count') ?: 8))->get(),
            'url' => FacultyMemberWebsite::directoryUrl($site),
        ];
    }
}
