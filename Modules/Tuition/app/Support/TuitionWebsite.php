<?php

namespace Modules\Tuition\Support;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\Department\Models\Department;
use Modules\Faculty\Models\Faculty;
use Modules\Tuition\Models\FeeCategory;
use Modules\Tuition\Models\Tuition;
use Platform\Context\SiteContext;
use Platform\Seo\Seo;
use Platform\Taxonomy\Term;
use Platform\Taxonomy\Vocabularies;
use Platform\Theme\LayoutResolver;

/**
 * /tuition-fees (FR-FEE-03): one fee category at a time — /tuition-fees/{code} (owner 2026-11-02:
 * /tuition-fees/international, not ?category=; the old ?category= redirects there), /tuition-fees the
 * first one — filtered by faculty, department and level (?faculty=, ?department=, ?level=), grouped by
 * faculty in the faculties' order; print-friendly.
 */
class TuitionWebsite
{
    public const PAGE = 'tuition-fees';

    public function respond(string $rest, SiteContext $context, string $locale): Response|RedirectResponse|null
    {
        $rest = trim($rest, '/');
        if ($context->isMicrosite() || ($rest !== self::PAGE && ! Str::startsWith($rest, self::PAGE.'/'))) {
            return null;
        }
        $code = $rest === self::PAGE ? null : Str::after($rest, self::PAGE.'/');
        if ($code !== null && str_contains($code, '/')) {
            return null;
        }

        $query = request()->query();
        $categories = FeeCategory::query()->frontendVisible()->ordered()->get();
        $find = fn (?string $asked): ?FeeCategory => $categories->first(fn (FeeCategory $c): bool => strcasecmp($c->code, (string) $asked) === 0);

        // The old ?category=international (menus, DIU's links) → its own address, the filters kept
        if (filled($query['category'] ?? null) && ($asked = $find((string) $query['category']))) {
            $filters = collect($query)->except('category')->all();

            return redirect(Seo::localized($asked->url(), $locale).($filters ? '?'.http_build_query($filters) : ''), 301);
        }
        $category = $code === null ? $categories->first() : $find($code);
        if ($code !== null && $category === null) {
            return null;
        }

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
            ->with(['program.department.faculty', 'program.level', 'program.majors', 'lines', 'category'])
            ->when($selected['level'], fn ($q, Term $level) => $q->whereHas('program', fn ($p) => $p->where('level_term_id', $level->id)))
            ->when($selected['department'], fn ($q, Department $d) => $q->whereHas('program', fn ($p) => $p->where('department_id', $d->id)))
            ->when($selected['faculty'], fn ($q, Faculty $f) => $q->whereHas('program.department', fn ($d) => $d->where('faculty_id', $f->id)))
            ->ordered()->get();

        app(Seo::class)->set([
            'title' => $category ? $category->name.' — '.__('tuition::tuition.tuition_fees') : __('tuition::tuition.tuition_fees'),
            'path' => $category?->url() ?? '/'.self::PAGE,
            'canonical' => url($category?->url() ?? '/'.self::PAGE),   // /tuition-fees and /tuition-fees/local are one page: the category's address
        ]);

        $layouts = app(LayoutResolver::class);

        return response()->view('tuition::listing.'.($layouts->for('tuition.listing') ?? 'grouped'), [
            'category' => $category,
            'categories' => $categories,
            'groups' => $this->groups($tuitions, $faculties),
            'choices' => ['faculties' => $faculties, 'departments' => $departments, 'levels' => $levels],
            'selected' => $selected,
            'options' => $layouts->options('tuition.listing'),
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
