<?php

namespace Modules\Leadership\Support;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\Leadership\Models\LeadershipTerm;
use Modules\Leadership\Models\Position;
use Platform\Context\SiteContext;
use Platform\Seo\Seo;
use Platform\Taxonomy\Term;
use Platform\Taxonomy\Vocabularies;
use Platform\Theme\LayoutResolver;

/**
 * /leadership (FR-LEAD-03): today's holders by group in the vocabulary's order, positions in
 * order; /leadership/former (FR-LEAD-05): past holders by position, latest first;
 * /leadership/{slug} (FR-LEAD-04): photo, position, tenure, message, biography.
 */
class LeadershipWebsite
{
    public const VIEWS = ['grid', 'cards', 'list'];

    public function respond(string $rest, SiteContext $context, string $locale): Response|RedirectResponse|null
    {
        $rest = trim($rest, '/');
        if ($context->isMicrosite() || ($rest !== LeadershipTerm::PATH && ! Str::startsWith($rest, LeadershipTerm::PATH.'/'))) {
            return null;
        }

        if ($rest === LeadershipTerm::PATH) {
            app(Seo::class)->set(['title' => __('leadership::leadership.plural'), 'path' => '/'.LeadershipTerm::PATH]);

            return response()->view('leadership::index', ['groups' => $this->groups(), 'layout' => $this->layout()]);
        }

        $slug = Str::after($rest, LeadershipTerm::PATH.'/');
        if ($slug === 'former') {
            app(Seo::class)->set(['title' => __('leadership::leadership.former'), 'path' => '/'.LeadershipTerm::PATH.'/former']);

            return response()->view('leadership::former', ['positions' => $this->former()]);
        }

        if (str_contains($slug, '/') || ! ($found = LeadershipTerm::findBySlug($slug, $locale))) {
            return null;
        }
        [$term, $current] = $found;
        if (! $term->isFrontendVisible() || ! $term->person()?->isActive()) {
            return null;
        }
        if (! $current) {
            return redirect(Seo::localized($term->url($locale), $locale), 301);   // FR-GEN-32
        }

        app(Seo::class)->forRecord($term);

        return response()->view('leadership::show', ['term' => $term, 'person' => $term->person(), 'position' => $term->position]);
    }

    /**
     * Groups (vocabulary order, then "no group") with their positions and today's holders.
     *
     * @return Collection<int, array{group: ?Term, positions: Collection<int, array{position: Position, terms: Collection<int, LeadershipTerm>}>}>
     */
    public function groups(): Collection
    {
        $positions = Position::query()->frontendVisible()->ordered()->get()
            ->map(fn (Position $p): array => ['position' => $p, 'terms' => $this->visible($p->currentTerms()->with('assignment.person')->get())])
            ->filter(fn (array $p): bool => $p['terms']->isNotEmpty());

        $order = array_keys(app(Vocabularies::class)->options(Position::GROUP));
        $terms = Term::query()->whereKey($order)->get()->sortBy(fn (Term $t): int => array_search($t->id, $order, true))->values();

        return $terms->map(fn (Term $t): array => ['group' => $t, 'positions' => $positions->filter(fn (array $p): bool => $p['position']->group_term_id === $t->id)->values()])
            ->push(['group' => null, 'positions' => $positions->filter(fn (array $p): bool => ! in_array($p['position']->group_term_id, $order, true))->values()])
            ->filter(fn (array $g): bool => $g['positions']->isNotEmpty())->values();
    }

    /** @return Collection<int, array{position: Position, terms: Collection<int, LeadershipTerm>}> */
    protected function former(): Collection
    {
        return Position::query()->frontendVisible()->ordered()->get()
            ->map(fn (Position $p): array => ['position' => $p, 'terms' => $this->visible(LeadershipTerm::query()->frontendVisible()->where('position_id', $p->id)
                ->former()->with('assignment.person')->get()->sortByDesc(fn (LeadershipTerm $t) => $t->assignment->ends_on)->values())])
            ->filter(fn (array $p): bool => $p['terms']->isNotEmpty())->values();
    }

    /** Drop terms whose person left (inactive) or whose directory is off. */
    protected function visible(Collection $terms): Collection
    {
        return $terms->filter(fn (LeadershipTerm $t): bool => (bool) $t->person()?->isActive())->values();
    }

    protected function layout(): string
    {
        $wanted = request()->query('view');
        $layout = is_string($wanted) && in_array($wanted, self::VIEWS, true) ? $wanted : (app(LayoutResolver::class)->for('leadership.listing') ?? 'grid');

        return in_array($layout, self::VIEWS, true) ? $layout : 'grid';
    }
}
