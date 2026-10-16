<?php

namespace Modules\Administration\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\Administration\Models\AdministrationCategory;
use Modules\Administration\Models\Member;
use Platform\Assignments\AssignmentEngine;
use Platform\Assignments\RoleCatalog;
use Platform\Context\SiteContext;
use Platform\Seo\Seo;
use Platform\Theme\LayoutResolver;

/**
 * /administration (FR-ADMN-04): members of public categories grouped by category in ladder
 * order; filters ?group=, ?category=, ?office=, search ?q= (name, designation); layout grid /
 * list / table. /administration/{slug} (FR-ADMN-05, when profile pages are on): photo,
 * designation, office, public contacts, biography and every current public assignment.
 */
class AdministrationWebsite
{
    public const VIEWS = ['grid', 'list', 'table'];

    protected const OFFICE = 'Modules\\Office\\Models\\Office';

    public function respond(string $rest, SiteContext $context, string $locale): Response|RedirectResponse|null
    {
        $rest = trim($rest, '/');
        if ($context->isMicrosite() || ($rest !== Member::PATH && ! Str::startsWith($rest, Member::PATH.'/'))) {
            return null;
        }

        if ($rest === Member::PATH) {
            app(Seo::class)->set(['title' => __('administration::administration.plural'), 'path' => '/'.Member::PATH]);

            return response()->view('administration::directory', $this->directory());
        }

        $slug = Str::after($rest, Member::PATH.'/');
        if (! settings('administration.profile_pages', true) || str_contains($slug, '/') || ! ($found = Member::findBySlug($slug, $locale))) {
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

        return response()->view('administration::profile', [
            'member' => $member,
            'person' => $member->personPresenter(),
            'office' => $this->office($member->primary_office_id),
            'places' => $this->places($member),
        ]);
    }

    /** @return array<string, mixed> */
    protected function directory(): array
    {
        $request = request();
        $categories = AdministrationCategory::query()->public()->ordered()->get();
        $offices = $this->offices();

        $members = Member::query()->frontendVisible()->with('category')
            ->when($request->filled('group'), fn (Builder $q) => $q->whereHas('category', fn ($c) => $c->where('group', (string) $request->query('group'))))
            ->when($request->filled('category'), fn (Builder $q) => $q->where('category_id', (int) $request->query('category')))
            ->when($request->filled('office'), fn (Builder $q) => $q->where('primary_office_id', (int) $request->query('office')))
            ->when(filled($search = trim((string) $request->query('q'))), fn (Builder $q) => $q->searchText($search))
            ->directoryOrder()->get();

        $layout = $request->query('view');
        $layout = is_string($layout) && in_array($layout, self::VIEWS, true) ? $layout : (app(LayoutResolver::class)->for('administration.directory') ?? 'grid');

        return [
            'groups' => $categories->map(fn (AdministrationCategory $c): array => ['category' => $c, 'members' => $members->where('category_id', $c->id)->values()])
                ->filter(fn (array $g): bool => $g['members']->isNotEmpty())->values(),
            'categories' => $categories,
            'groupOptions' => $categories->pluck('group')->unique()->values(),
            'offices' => $offices,
            'officeNames' => $offices->pluck('name', 'id'),
            'total' => $members->count(),
            'layout' => in_array($layout, self::VIEWS, true) ? $layout : 'grid',
            'intro' => filled($intro = settings('administration.intro')) ? (string) $intro : null,
        ];
    }

    /**
     * Current public assignments with the place's name and link (AC-ADMN-03).
     *
     * @return Collection<int, array{place: string, url: ?string, role: string}>
     */
    protected function places(Member $member): Collection
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

    protected function offices(): Collection
    {
        if (! class_exists(self::OFFICE)) {
            return collect();
        }
        $used = Member::query()->frontendVisible()->whereNotNull('primary_office_id')->distinct()->pluck('primary_office_id')->all();

        return $used === [] ? collect() : (self::OFFICE)::query()->frontendVisible()->whereKey($used)->ordered()->get();
    }

    protected function office(?int $id): mixed
    {
        return $id && class_exists(self::OFFICE) ? (self::OFFICE)::query()->frontendVisible()->find($id) : null;
    }
}
