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
 * order; filters ?group=, ?office=, search ?q= (name, designation); layout grid /
 * list / table. /administration/{slug}: a section (category; the old ?category={id} redirects there),
 * else a person (FR-ADMN-05, when profile pages are on): photo,
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
            // The old ?category={id} (and ?category={slug}) → the section's own address
            if (filled($asked = request()->query('category')) && ($category = $this->category((string) $asked, $locale))) {
                return redirect(Seo::localized($category->url($locale), $locale).$this->query(['category']), 301);
            }
            app(Seo::class)->set(['title' => __('administration::administration.plural'), 'path' => '/'.Member::PATH]);

            return response()->view('administration::directory', $this->directory());
        }

        $slug = Str::after($rest, Member::PATH.'/');
        if (str_contains($slug, '/')) {
            return null;
        }

        // A section: /administration/{category} (DIU /admin/joint-director)
        if ($found = AdministrationCategory::findBySlug($slug, $locale)) {
            [$category, $current] = $found;
            if (! $category->is_public || ! $category->is_active) {
                return null;
            }
            if (! $current) {
                return redirect(Seo::localized($category->url($locale), $locale).$this->query(), 301);   // FR-GEN-32
            }
            app(Seo::class)->set(['title' => $category->name.' — '.__('administration::administration.plural'), 'path' => $category->url($locale)]);

            return response()->view('administration::directory', $this->directory($category));
        }

        if (! settings('administration.profile_pages', true) || ! ($found = Member::findBySlug($slug, $locale))) {
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
            ...$this->sections(),
        ]);
    }

    /** A public category by id (old links) or slug */
    protected function category(string $asked, string $locale): ?AdministrationCategory
    {
        $category = ctype_digit($asked) ? AdministrationCategory::query()->find((int) $asked) : (AdministrationCategory::findBySlug($asked, $locale)[0] ?? null);

        return $category && $category->is_public && $category->is_active ? $category : null;
    }

    /** The rest of the query string, without some keys ("?q=…&view=…") */
    protected function query(array $without = []): string
    {
        $rest = collect(request()->query())->except($without)->all();

        return $rest ? '?'.http_build_query($rest) : '';
    }

    /** @return array<string, mixed> */
    protected function directory(?AdministrationCategory $category = null): array
    {
        $request = request();
        $categories = AdministrationCategory::query()->public()->ordered()->get();
        $offices = $this->offices();

        $members = Member::query()->frontendVisible()->with('category')
            ->when($request->filled('group'), fn (Builder $q) => $q->whereHas('category', fn ($c) => $c->where('group', (string) $request->query('group'))))
            ->when($category, fn (Builder $q) => $q->where('category_id', $category->id))
            ->when($request->filled('office'), fn (Builder $q) => $q->where('primary_office_id', (int) $request->query('office')))
            ->when(filled($search = trim((string) $request->query('q'))), fn (Builder $q) => $q->searchText($search))
            ->directoryOrder()->get();

        $layout = $request->query('view');
        $layout = is_string($layout) && in_array($layout, self::VIEWS, true) ? $layout : (app(LayoutResolver::class)->for('administration.directory') ?? 'grid');

        return [
            'groups' => $categories->map(fn (AdministrationCategory $c): array => ['category' => $c, 'members' => $members->where('category_id', $c->id)->values()])
                ->filter(fn (array $g): bool => $g['members']->isNotEmpty())->values(),
            ...$this->sections($categories),
            'search' => trim((string) $request->query('q')),
            'groupOptions' => $categories->pluck('group')->unique()->values(),
            'offices' => $offices,
            'officeNames' => $offices->pluck('name', 'id'),
            'total' => $members->count(),
            'layout' => in_array($layout, self::VIEWS, true) ? $layout : 'grid',
            'intro' => filled($intro = settings('administration.intro')) ? (string) $intro : null,
            'category' => $category,   // the section asked for (/administration/{slug}), or none
        ];
    }

    /**
     * The public categories and their people before any filter — the sections a theme lists beside
     * the directory and a profile (DIU's "Management (5)").
     *
     * @return array{categories: Collection, counts: array<int, int>}
     */
    protected function sections(?Collection $categories = null): array
    {
        return [
            'categories' => $categories ?? AdministrationCategory::query()->public()->ordered()->get(),
            'counts' => Member::query()->frontendVisible()->reorder()->selectRaw('category_id, count(*) as people')->groupBy('category_id')->pluck('people', 'category_id')->map(fn ($n): int => (int) $n)->all(),
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
