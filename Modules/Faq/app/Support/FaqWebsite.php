<?php

namespace Modules\Faq\Support;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Modules\Faq\Models\Faq;
use Platform\Context\MicrositeResolver;
use Platform\Context\SiteContext;
use Platform\Relations\RelationEngine;
use Platform\Seo\Seo;
use Platform\Taxonomy\Term;
use Platform\Taxonomy\Vocabularies;
use Platform\Theme\LayoutResolver;

/**
 * /faq (FR-FAQ-02): the main site's FAQs (published there), by category as accordions, with
 * instant search in the browser and FAQPage JSON-LD (FR-FAQ-04); ?category={slug} keeps one
 * category and its sub-categories. A microsite's /{entity}/faq shows the FAQs shown in it
 * (FR-FAQ-03). Category descriptions (the term's text) above each group.
 */
class FaqWebsite
{
    public function respond(string $rest, SiteContext $context, string $locale): Response|RedirectResponse|null
    {
        if (trim($rest, '/') !== Faq::PATH) {
            return null;
        }

        $faqs = static::forContext($context)->ordered()->with('terms')->get();
        if ($context->isMicrosite() && $faqs->isEmpty()) {
            return null;
        }

        $base = $context->isMicrosite() ? rtrim((string) app(MicrositeResolver::class)->urlFor($context->entity, $locale), '/') : '';
        app(Seo::class)->set(['title' => __('faq::faq.title'), 'path' => $base.'/'.Faq::PATH]);
        [$groups, $selected] = static::groups($faqs, $locale, request()->query('category'));
        $layout = app(LayoutResolver::class)->for('faq.listing') ?? 'accordion';

        return response()->view('faq::index', [
            'groups' => $groups,
            'selected' => $selected,
            'faqs' => $faqs,
            'layout' => in_array($layout, ['accordion', 'grouped'], true) ? $layout : 'accordion',
            'jsonLd' => static::jsonLd($groups->flatMap(fn (array $g) => $g['faqs']), $locale),   // what the page shows
            'helpful' => (bool) settings('faq.show_helpful_buttons', true),
        ]);
    }

    /** Visible FAQs of a context: the main site's (site:0), or those shown in an entity. */
    public static function forContext(SiteContext $context)
    {
        return app(RelationEngine::class)->for($context->isMicrosite() ? $context->key() : RelationEngine::SITE_KEY)
            ->content(Faq::class)->query();
    }

    /**
     * FAQs grouped by category in the vocabulary's order, uncategorised last; a ?category= keeps
     * that category and its sub-categories.
     *
     * @param  Collection<int, Faq>  $faqs
     * @return array{0: Collection<int, array{category: ?Term, faqs: Collection<int, Faq>}>, 1: ?Term}
     */
    public static function groups(Collection $faqs, string $locale, mixed $wanted = null): array
    {
        $termOf = fn (Faq $f): ?int => $f->termIdsIn(Faq::CATEGORY)[0] ?? null;
        $order = array_keys(app(Vocabularies::class)->options(Faq::CATEGORY));
        $terms = Term::query()->whereKey($order)->get()->sortBy(fn (Term $t): int => array_search($t->id, $order, true))->values();

        $selected = is_string($wanted) && $wanted !== '' ? $terms->first(fn (Term $t): bool => $t->slug($locale) === $wanted) : null;
        if ($selected) {
            $keep = [$selected->id, ...$selected->descendantIds()];
            $faqs = $faqs->filter(fn (Faq $f): bool => in_array($termOf($f), $keep, true))->values();
        }

        $groups = $terms->map(fn (Term $t): array => ['category' => $t, 'faqs' => $faqs->filter(fn (Faq $f): bool => $termOf($f) === $t->id)->values()])
            ->push(['category' => null, 'faqs' => $faqs->filter(fn (Faq $f): bool => $termOf($f) === null)->values()])
            ->filter(fn (array $g): bool => $g['faqs']->isNotEmpty())->values();

        return [$groups, $selected];
    }

    /**
     * FAQPage structured data (FR-FAQ-04).
     *
     * @param  Collection<int, Faq>  $faqs
     * @return array<string, mixed>
     */
    public static function jsonLd(Collection $faqs, string $locale): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $faqs->take(100)->map(fn (Faq $f): array => [
                '@type' => 'Question',
                'name' => $f->getTranslation('question', $locale, true),
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => trim(strip_tags((string) $f->getTranslation('answer', $locale, true)))],
            ])->values()->all(),
        ];
    }
}
