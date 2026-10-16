<?php

namespace Modules\Committee\Support;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\Committee\Models\Committee;
use Platform\Context\MicrositeResolver;
use Platform\Context\SiteContext;
use Platform\Relations\RelationEngine;
use Platform\Seo\Seo;
use Platform\Taxonomy\Term;
use Platform\Taxonomy\Vocabularies;
use Platform\Theme\LayoutResolver;

/**
 * /committees and /{entity}/committees (FR-COM-04/06): the running committees shown in this
 * place, by category, as a table / cards / list; ?category={slug}. /committees/{slug} (and
 * under a microsite): description, term, order PDF, members in role order with their note,
 * previous terms. A committee not shown in this place answers 404 (AC-COM-02).
 */
class CommitteeWebsite
{
    public const VIEWS = ['table', 'cards', 'list'];

    public function respond(string $rest, SiteContext $context, string $locale): Response|RedirectResponse|null
    {
        $rest = trim($rest, '/');
        if ($rest !== Committee::PATH && ! Str::startsWith($rest, Committee::PATH.'/')) {
            return null;
        }

        $base = $context->isMicrosite() ? rtrim((string) app(MicrositeResolver::class)->urlFor($context->entity, $locale), '/') : '';
        $shown = fn () => app(RelationEngine::class)->for($context->key())->content(Committee::class)->query();

        if ($rest === Committee::PATH) {
            $committees = $shown()->running()->with(['category', 'assignments'])->ordered()->get();
            if ($context->isMicrosite() && $committees->isEmpty()) {
                return null;
            }
            app(Seo::class)->set(['title' => __('committee::committee.plural'), 'path' => $base.'/'.Committee::PATH]);

            return response()->view('committee::index', $this->listing($committees, $locale) + ['base' => $base]);
        }

        $slug = Str::after($rest, Committee::PATH.'/');
        if (str_contains($slug, '/') || ! ($found = Committee::findBySlug($slug, $locale))) {
            return null;
        }
        [$committee, $current] = $found;
        if (! $shown()->whereKey($committee->getKey())->exists()) {
            return null;   // not shown here (AC-COM-02)
        }
        if (! $current) {
            return redirect(Seo::localized($base.'/'.Committee::PATH.'/'.$committee->slug($locale), $locale), 301);   // FR-GEN-32
        }

        app(Seo::class)->forRecord($committee);

        return response()->view('committee::show', [
            'committee' => $committee,
            'members' => $committee->people(),
            'previous' => $committee->previousTerms(),
            'next' => $committee->nextTerm()->first()?->isFrontendVisible() ? $committee->nextTerm()->first() : null,
            'orderPdf' => $committee->firstMedia('order_pdf'),
            'base' => $base,
        ]);
    }

    /**
     * @param  Collection<int, Committee>  $committees
     * @return array<string, mixed>
     */
    protected function listing(Collection $committees, string $locale): array
    {
        $order = array_keys(app(Vocabularies::class)->options(Committee::CATEGORY));
        $terms = Term::query()->whereKey($order)->get()->sortBy(fn (Term $t): int => array_search($t->id, $order, true))->values();

        $wanted = request()->query('category');
        $selected = is_string($wanted) && $wanted !== '' ? $terms->first(fn (Term $t): bool => $t->slug($locale) === $wanted) : null;
        $used = $committees->pluck('category_term_id')->filter()->unique()->all();
        if ($selected) {
            $committees = $committees->where('category_term_id', $selected->id)->values();
        }

        $groups = $terms->map(fn (Term $t): array => ['category' => $t, 'committees' => $committees->where('category_term_id', $t->id)->values()])
            ->push(['category' => null, 'committees' => $committees->filter(fn (Committee $c): bool => ! in_array($c->category_term_id, $order, true))->values()])
            ->filter(fn (array $g): bool => $g['committees']->isNotEmpty())->values();

        $layout = request()->query('view');
        $layout = is_string($layout) && in_array($layout, self::VIEWS, true) ? $layout : (app(LayoutResolver::class)->for('committee.listing') ?? 'table');

        return [
            'groups' => $groups,
            'categories' => $terms->filter(fn (Term $t): bool => in_array($t->id, $used, true))->values(),
            'selected' => $selected,
            'layout' => in_array($layout, self::VIEWS, true) ? $layout : 'table',
        ];
    }
}
