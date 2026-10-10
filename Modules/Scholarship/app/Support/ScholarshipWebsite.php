<?php

namespace Modules\Scholarship\Support;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\Scholarship\Models\Scholarship;
use Platform\Context\SiteContext;
use Platform\Seo\Seo;
use Platform\Taxonomy\Term;
use Platform\Taxonomy\Vocabularies;
use Platform\Theme\LayoutResolver;

/**
 * /scholarships (FR-FEE-10): the top-level scholarships in order; /scholarships/{slug}: one
 * scholarship (details, eligibility, how to apply, apply link, files) with its parts — open
 * ones first, closed ones after. An old address answers 301 to the current one.
 */
class ScholarshipWebsite
{
    public function respond(string $rest, SiteContext $context, string $locale): Response|RedirectResponse|null
    {
        $rest = trim($rest, '/');

        // A menu's "Category" item (FR-TAX-05): /scholarship/category/{slug} → the listing on that category
        if (! $context->isMicrosite() && preg_match('#^scholarship/category/([^/]+)$#', $rest, $m)) {
            return redirect(Seo::localized('/'.Scholarship::PATH, $locale).'?category='.rawurlencode($m[1]), 301);
        }

        if ($context->isMicrosite() || ($rest !== Scholarship::PATH && ! Str::startsWith($rest, Scholarship::PATH.'/'))) {
            return null;
        }

        $slug = Str::after($rest, Scholarship::PATH.'/');
        if ($rest === Scholarship::PATH) {
            app(Seo::class)->set(['title' => __('scholarship::scholarship.plural'), 'path' => '/'.Scholarship::PATH]);

            return response()->view('scholarship::listing', $this->listing($locale));
        }

        if (str_contains($slug, '/') || ! ($found = Scholarship::findBySlug($slug, $locale))) {
            return null;
        }

        [$scholarship, $current] = $found;
        if (! $scholarship->isFrontendVisible() || ($scholarship->parent && ! $scholarship->parent->isFrontendVisible())) {
            return null;
        }
        if (! $current) {
            return redirect(Seo::localized($scholarship->url($locale), $locale), 301);   // FR-GEN-32
        }

        app(Seo::class)->forRecord($scholarship);

        return response()->view('scholarship::show', [
            'scholarship' => $scholarship,
            'parent' => $scholarship->parent,
            'parts' => $scholarship->parts()->frontendVisible()->get()->sortBy(fn (Scholarship $s): int => $s->isClosed() ? 1 : 0)->values(),
        ]);
    }

    /**
     * The top-level scholarships grouped by category in the vocabulary's order, the ones
     * without a category last; ?category={slug} keeps one category and its sub-categories.
     *
     * @return array{groups: Collection<int, array{category: ?Term, scholarships: Collection<int, Scholarship>}>, categories: Collection<int, Term>, selected: ?Term, scholarships: Collection<int, Scholarship>}
     */
    protected function listing(string $locale): array
    {
        $scholarships = Scholarship::query()->frontendVisible()->topLevel()->ordered()->with('terms')
            ->withCount(['parts' => fn ($q) => $q->frontendVisible()])->get();
        $termOf = fn (Scholarship $s): ?int => $s->termIdsIn(Scholarship::CATEGORY)[0] ?? null;

        $order = array_keys(app(Vocabularies::class)->options(Scholarship::CATEGORY));   // parents before their children
        $terms = Term::query()->whereKey($order)->get()->sortBy(fn (Term $t): int => array_search($t->id, $order, true))->values();
        $used = $scholarships->map($termOf)->filter()->unique()->all();
        $categories = $terms->filter(fn (Term $t): bool => in_array($t->id, $used, true))->values();

        $wanted = request()->query('category');
        $selected = is_string($wanted) ? $terms->first(fn (Term $t): bool => $t->slug($locale) === $wanted) : null;
        if ($selected) {
            $keep = [$selected->id, ...$selected->descendantIds()];
            $scholarships = $scholarships->filter(fn (Scholarship $s): bool => in_array($termOf($s), $keep, true))->values();
        }

        $groups = $terms->map(fn (Term $t): array => ['category' => $t, 'scholarships' => $scholarships->filter(fn (Scholarship $s): bool => $termOf($s) === $t->id)->values()])
            ->push(['category' => null, 'scholarships' => $scholarships->filter(fn (Scholarship $s): bool => $termOf($s) === null)->values()])
            ->filter(fn (array $g): bool => $g['scholarships']->isNotEmpty())->values();

        $layout = app(LayoutResolver::class)->for('scholarship.listing') ?? 'cards';

        return [
            'groups' => $groups, 'categories' => $categories, 'selected' => $selected, 'scholarships' => $scholarships,
            'layout' => in_array($layout, ['cards', 'accordion'], true) ? $layout : 'cards',
            'intro' => filled($intro = settings('scholarship.intro')) ? (string) $intro : null,
            'outro' => filled($outro = settings('scholarship.outro')) ? (string) $outro : null,
        ];
    }
}
