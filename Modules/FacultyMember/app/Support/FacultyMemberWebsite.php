<?php

namespace Modules\FacultyMember\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\Department\Models\Department;
use Modules\Faculty\Models\Faculty;
use Modules\FacultyMember\Models\FacultyMember;
use Platform\Assignments\AssignmentEngine;
use Platform\Assignments\RoleCatalog;
use Platform\Context\MicrositeResolver;
use Platform\Context\SiteContext;
use Platform\Seo\Seo;
use Platform\Taxonomy\Term;
use Platform\Taxonomy\Vocabularies;
use Platform\Theme\LayoutResolver;

/**
 * /faculty-members (FR-FM-03): teachers in designation order; filters ?faculty=, ?department=,
 * ?designation=, search ?q=; layout grid / list / cards. A Department microsite's
 * /faculty-members shows its own (primary or also there), a Faculty microsite's its
 * departments' (FR-FM-05). /faculty-members/{slug} (FR-FM-04, when profile pages are on):
 * photo, designation, departments, public contacts, links, biography, profile sections and
 * every current public assignment.
 */
class FacultyMemberWebsite
{
    public const VIEWS = ['grid', 'list', 'cards'];

    public const PER_PAGE = 30;

    public function respond(string $rest, SiteContext $context, string $locale): Response|RedirectResponse|null
    {
        $rest = trim($rest, '/');
        if ($rest !== FacultyMember::PATH && ! Str::startsWith($rest, FacultyMember::PATH.'/')) {
            return null;
        }
        $departments = self::departmentsOf($context);
        if ($departments === []) {
            return null;   // only Faculty and Department microsites have teachers
        }

        if ($rest === FacultyMember::PATH) {
            app(Seo::class)->set(['title' => __('facultymember::facultymember.plural'), 'path' => self::directoryUrl($context, $locale)]);

            return response()->view('facultymember::directory', $this->directory($departments, $context));
        }

        $slug = Str::after($rest, FacultyMember::PATH.'/');
        if ($context->isMicrosite() || ! settings('faculty-member.profile_pages', true) || str_contains($slug, '/') || ! ($found = FacultyMember::findBySlug($slug, $locale))) {
            return null;
        }
        [$member, $current] = $found;
        if (! $member->isFrontendVisible()) {
            return null;
        }
        if (! $current) {
            return redirect(Seo::localized($member->url($locale), $locale), 301);   // FR-GEN-32
        }

        app(Seo::class)->forRecord($member);
        $member->load(['designation', 'department', 'departments']);

        return response()->view('facultymember::profile', [
            'member' => $member,
            'person' => $member->personPresenter(),
            'departments' => collect([$member->department, ...$member->departments])->filter(fn ($d) => $d?->isFrontendVisible())->unique('id')->values(),
            'places' => $this->places($member),
        ]);
    }

    /** Department ids of a place: null = all (main site), [] = this place has no teachers. */
    public static function departmentsOf(SiteContext $context): ?array
    {
        if (! $context->isMicrosite()) {
            return null;
        }

        return match ($context->entity->getMorphClass()) {
            'department.department' => [(int) $context->entity->getKey()],
            'faculty.faculty' => Department::query()->withoutGlobalScopes()->whereNull('deleted_at')->where('faculty_id', $context->entity->getKey())->pluck('id')->map(fn ($id) => (int) $id)->all() ?: [0],
            default => [],
        };
    }

    public static function directoryUrl(SiteContext $context, ?string $locale = null): string
    {
        $base = $context->isMicrosite() ? rtrim((string) app(MicrositeResolver::class)->urlFor($context->entity, $locale), '/') : '';

        return $base.'/'.FacultyMember::PATH;
    }

    /** @return array<string, mixed> */
    protected function directory(?array $departments, SiteContext $context): array
    {
        $request = request();
        $base = FacultyMember::query()->frontendVisible()->when($departments !== null, fn (Builder $q) => $q->inDepartments($departments));

        $faculties = $departments === null ? Faculty::query()->frontendVisible()->ordered()->get() : collect();
        $faculty = $faculties->first(fn (Faculty $f): bool => strcasecmp((string) $f->short_name, (string) $request->query('faculty')) === 0);
        $departmentChoices = $departments === null || count($departments) > 1
            ? Department::query()->frontendVisible()->when($departments !== null, fn ($q) => $q->whereKey($departments))
                ->when($faculty, fn ($q) => $q->where('faculty_id', $faculty->id))->ordered()->get()
            : collect();
        $department = $departmentChoices->first(fn (Department $d): bool => strcasecmp((string) $d->short_name, (string) $request->query('department')) === 0);
        $designations = Term::query()->where('vocabulary_id', app(Vocabularies::class)->find(FacultyMember::DESIGNATION)->id)
            ->whereIn('id', (clone $base)->whereNotNull('designation_term_id')->distinct()->pluck('designation_term_id'))->ordered()->get();

        $members = (clone $base)->with(['designation', 'department'])
            ->when($faculty && ! $department, fn (Builder $q) => $q->inDepartments(Department::query()->where('faculty_id', $faculty->id)->pluck('id')->all() ?: [0]))
            ->when($department, fn (Builder $q) => $q->inDepartments([$department->id]))
            ->when($request->filled('designation'), fn (Builder $q) => $q->where('designation_term_id', (int) $request->query('designation')))
            ->when(filled($search = trim((string) $request->query('q'))), fn (Builder $q) => $q->searchText($search))
            ->directoryOrder()->paginate(self::PER_PAGE)->withQueryString();

        $layout = $request->query('view');
        $layout = is_string($layout) && in_array($layout, self::VIEWS, true) ? $layout : (app(LayoutResolver::class)->for('faculty-member.directory') ?? 'grid');

        return [
            'members' => $members,
            'faculties' => $faculties,
            'departments' => $departmentChoices,
            'designations' => $designations,
            'layout' => in_array($layout, self::VIEWS, true) ? $layout : 'grid',
            'intro' => ! $context->isMicrosite() && filled($intro = settings('faculty-member.intro')) ? (string) $intro : null,
        ];
    }

    /** Current public assignments with the place's name and link (AC-FM-02). */
    protected function places(FacultyMember $member): Collection
    {
        return app(AssignmentEngine::class)->assignmentsOf($member)
            ->filter(fn ($a): bool => $a->is_active && $a->is_public && (! $a->ends_on || $a->ends_on->isFuture() || $a->ends_on->isToday()))
            ->load('assignable')
            ->filter(fn ($a): bool => $a->assignable !== null && (! method_exists($a->assignable, 'isFrontendVisible') || $a->assignable->isFrontendVisible()))
            ->map(fn ($a): array => [
                'place' => (string) ($a->assignable->name ?? $a->assignable->title ?? ''),
                'url' => method_exists($a->assignable, 'url') ? $a->assignable->url() : null,
                'role' => (string) ($a->role_label ?: app(RoleCatalog::class)->label($a->assignable_type, $a->role_key)),
            ])->values();
    }
}
