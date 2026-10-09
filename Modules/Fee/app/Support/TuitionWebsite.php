<?php

namespace Modules\Fee\Support;

use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Modules\Department\Models\Department;
use Modules\Faculty\Models\Faculty;
use Modules\Fee\Models\FeeCategory;
use Modules\Fee\Models\Tuition;
use Platform\Context\SiteContext;
use Platform\Seo\Seo;
use Platform\Taxonomy\Term;
use Platform\Taxonomy\Vocabularies;
use Platform\Theme\LayoutResolver;

/**
 * /tuition-fees (FR-FEE-03): one fee category at a time (?category=local-tuition, the
 * first one by default), filtered by faculty, department and level, grouped by faculty
 * in the faculties' order; print-friendly.
 */
class TuitionWebsite
{
    public const PAGE = 'tuition-fees';

    public const CALCULATOR = 'waiver-calculator';

    public function respond(string $rest, SiteContext $context, string $locale): ?Response
    {
        if (! $context->isMicrosite() && trim($rest, '/') === self::CALCULATOR) {   // FR-FEE-06
            app(Seo::class)->set(['title' => __('fee::fee.calculator.title'), 'path' => '/'.self::CALCULATOR]);

            return response()->view('fee::calculator.page');
        }

        if ($context->isMicrosite() || trim($rest, '/') !== self::PAGE) {
            return null;
        }

        $query = request()->query();
        $categories = FeeCategory::query()->frontendVisible()->ordered()->get();
        $category = $categories->first(fn (FeeCategory $c): bool => strcasecmp($c->code, (string) ($query['category'] ?? '')) === 0) ?? $categories->first();

        $faculties = Faculty::query()->frontendVisible()->ordered()->get();
        $departments = Department::query()->frontendVisible()->ordered()->get();
        $levels = Term::query()->where('vocabulary_id', app(Vocabularies::class)->find('program.level')->id)->where('is_active', true)->ordered()->get();

        $selected = [
            'faculty' => $faculties->first(fn (Faculty $f): bool => strcasecmp($f->short_name, (string) ($query['faculty'] ?? '')) === 0),
            'department' => $departments->first(fn (Department $d): bool => strcasecmp($d->short_name, (string) ($query['department'] ?? '')) === 0),
            'level' => $levels->first(fn (Term $t): bool => is_string($query['level'] ?? null) && $t->slug($locale) === $query['level']),
        ];

        $tuitions = $category === null ? collect() : Tuition::query()->visible()
            ->where('fee_category_id', $category->id)
            ->with(['program.department.faculty', 'program.level', 'lines', 'category'])
            ->when($selected['level'], fn ($q, Term $level) => $q->whereHas('program', fn ($p) => $p->where('level_term_id', $level->id)))
            ->when($selected['department'], fn ($q, Department $d) => $q->whereHas('program', fn ($p) => $p->where('department_id', $d->id)))
            ->when($selected['faculty'], fn ($q, Faculty $f) => $q->whereHas('program.department', fn ($d) => $d->where('faculty_id', $f->id)))
            ->ordered()->get();

        app(Seo::class)->set(['title' => __('fee::fee.tuition_fees'), 'path' => '/'.self::PAGE]);

        $layouts = app(LayoutResolver::class);

        return response()->view('fee::tuition.'.($layouts->for('fee.tuition-table') ?? 'grouped'), [
            'category' => $category,
            'categories' => $categories,
            'groups' => $this->groups($tuitions, $faculties),
            'choices' => ['faculties' => $faculties, 'departments' => $departments, 'levels' => $levels],
            'selected' => $selected,
            'options' => $layouts->options('fee.tuition-table'),
        ]);
    }

    /** @return Collection<int, array{faculty: Faculty, tuitions: Collection<int, Tuition>}> faculties in their order */
    protected function groups(Collection $tuitions, Collection $faculties): Collection
    {
        return $faculties
            ->map(fn (Faculty $f): array => ['faculty' => $f, 'tuitions' => $tuitions->filter(fn (Tuition $t): bool => $t->program?->department?->faculty_id === $f->id)->values()])
            ->filter(fn (array $g): bool => $g['tuitions']->isNotEmpty())
            ->values();
    }
}
