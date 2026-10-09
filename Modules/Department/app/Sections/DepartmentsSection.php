<?php

namespace Modules\Department\Sections;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Collection;
use Modules\Department\Models\Department;
use Modules\Faculty\Models\Faculty;
use Platform\Homepage\Section;
use Platform\Homepage\SectionContext;
use Platform\Statistics\StatContext;

/**
 * Homepage section "Departments" (FR-DEPT-09): the listed departments in order. On a
 * Faculty microsite it shows that Faculty's departments (FR-FAC-06); on the main site
 * all of them, or one Faculty's when the section says so.
 */
class DepartmentsSection extends Section
{
    public static function key(): string
    {
        return 'department.list';
    }

    public static function label(): string
    {
        return __('department::department.section_label');
    }

    public static function view(): string
    {
        return 'department::sections.departments';
    }

    public static function settingsSchema(): array
    {
        return [
            Select::make('faculty_id')->label(__('department::department.fields.section_faculty'))
                ->options(fn (): array => Faculty::query()->ordered()->get()->mapWithKeys(fn (Faculty $f): array => [$f->id => "{$f->short_name} — {$f->name}"])->all())
                ->placeholder(__('department::department.fields.section_all_faculties')),
            TextInput::make('limit')->label(__('department::department.fields.section_limit'))->numeric()->minValue(1)->maxValue(48)->placeholder('12'),
        ];
    }

    public function items(SectionContext $ctx): Collection
    {
        $ids = StatContext::of($ctx->ownerKey)->idsOf((new Department)->getMorphClass());

        return Department::query()->listed()
            ->when($ids !== null, fn ($q) => $q->whereKey($ids))
            ->when($ctx->setting('faculty_id'), fn ($q, $faculty) => $q->where('faculty_id', (int) $faculty))
            ->limit((int) ($ctx->setting('limit') ?: 12))
            ->get();
    }
}
