<?php

namespace Modules\Achievements\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\Achievements\Models\Achievement;
use Platform\Context\MicrositeResolver;
use Platform\Context\SiteContext;
use Platform\Relations\RelationEngine;
use Platform\Seo\Seo;
use Platform\Taxonomy\Term;
use Platform\Taxonomy\Vocabularies;
use Platform\Theme\LayoutResolver;

/**
 * /achievements and /{entity}/achievements (FR-ACH-02): the place's achievements, newest first by
 * the date achieved; ?year=, ?level=, ?category= (sub-categories included); cards / timeline /
 * grid. /achievements/{slug} (also under a microsite, only where it is shown).
 */
class AchievementsWebsite
{
    public const VIEWS = ['cards', 'timeline', 'grid'];

    public function respond(string $rest, SiteContext $context, string $locale): Response|RedirectResponse|null
    {
        $rest = trim($rest, '/');
        if ($rest !== Achievement::PATH && ! Str::startsWith($rest, Achievement::PATH.'/')) {
            return null;
        }
        $base = $context->isMicrosite() ? rtrim((string) app(MicrositeResolver::class)->urlFor($context->entity, $locale), '/') : '';

        if ($rest === Achievement::PATH) {
            return $this->listing($context, $base, $locale);
        }

        $slug = Str::after($rest, Achievement::PATH.'/');
        if (str_contains($slug, '/') || ! ($found = Achievement::findBySlug($slug, $locale))) {
            return null;
        }
        [$achievement, $current] = $found;
        if (! $this->shown($context)->whereKey($achievement->getKey())->exists()) {
            return null;
        }
        if (! $current) {
            return redirect(Seo::localized($base.'/'.Achievement::PATH.'/'.$achievement->slug($locale), $locale), 301);
        }

        $achievement->countView();
        app(Seo::class)->forRecord($achievement);

        return response()->view('achievements::show', [
            'achievement' => $achievement,
            'achievers' => $achievement->people(Achievement::ROLE),
            'gallery' => $achievement->mediaIn('gallery'),
            'post' => $achievement->relatedPost(),
            'base' => $base,
        ]);
    }

    /** Visible achievements of this place: the main site's, or an entity's and those below it. */
    protected function shown(SiteContext $context): Builder
    {
        return app(RelationEngine::class)->for($context->isMicrosite() ? $context->key() : RelationEngine::SITE_KEY)
            ->content(Achievement::class)->includeDescendants($context->isMicrosite())->query()->newest();
    }

    protected function listing(SiteContext $context, string $base, string $locale): ?Response
    {
        $request = request();
        if ($context->isMicrosite() && ! $this->shown($context)->exists()) {
            return null;
        }
        app(Seo::class)->set(['title' => __('achievements::achievements.plural'), 'path' => $base.'/'.Achievement::PATH]);

        $categories = $this->terms();
        $wanted = $request->query('category');
        $category = is_string($wanted) && $wanted !== '' ? $categories->first(fn (Term $t): bool => $t->slug($locale) === $wanted) : null;
        $level = in_array($request->query('level'), Achievement::LEVELS, true) ? (string) $request->query('level') : null;
        $year = preg_match('/^\d{4}$/', (string) $request->query('year')) ? (int) $request->query('year') : null;
        $date = (new Achievement)->qualifyColumn('achieved_on');

        $achievements = $this->shown($context)
            ->when($category, fn (Builder $query) => $query->whereHas('terms', fn (Builder $t) => $t->whereIn('core_terms.id', [$category->id, ...$category->descendantIds()])))
            ->ofLevel($level)
            ->when($year, fn (Builder $query) => $query->whereYear($date, $year))
            ->paginate(12)->withQueryString();

        $view = $request->query('view');
        $layout = is_string($view) && in_array($view, self::VIEWS, true) ? $view : (app(LayoutResolver::class)->for('achievements.listing') ?? 'cards');

        return response()->view('achievements::index', [
            'achievements' => $achievements,
            'categories' => $categories->whereNull('parent_id')->values(),
            'category' => $category,
            'level' => $level,
            'year' => $year,
            'years' => $this->shown($context)->reorder()->selectRaw("DISTINCT YEAR({$date}) as y")->orderByDesc('y')->pluck('y')->map(fn ($y) => (int) $y)->all(),
            'layout' => in_array($layout, self::VIEWS, true) ? $layout : 'cards',
            'base' => $base,
        ]);
    }

    /** @return Collection<int, Term> in the vocabulary's order */
    protected function terms(): Collection
    {
        $order = array_keys(app(Vocabularies::class)->options(Achievement::CATEGORY));

        return Term::query()->whereKey($order)->get()->sortBy(fn (Term $t): int => array_search($t->id, $order, true))->values();
    }
}
