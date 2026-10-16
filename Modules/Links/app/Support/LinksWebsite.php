<?php

namespace Modules\Links\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Modules\Links\Models\Link;
use Platform\Context\MicrositeResolver;
use Platform\Context\SiteContext;
use Platform\Relations\RelationEngine;
use Platform\Seo\Seo;
use Platform\Taxonomy\Term;
use Platform\Taxonomy\Vocabularies;
use Platform\Theme\LayoutResolver;

/**
 * /links and /{entity}/links (FR-LINK-02): the place's links by group (in the group's order,
 * dragged order inside), links without a group last; tiles or a list.
 */
class LinksWebsite
{
    public const VIEWS = ['tiles', 'list'];

    public function respond(string $rest, SiteContext $context, string $locale): ?Response
    {
        if (trim($rest, '/') !== Link::PATH) {
            return null;
        }
        $key = $context->isMicrosite() ? $context->key() : RelationEngine::SITE_KEY;
        $links = self::shown($key)->with('terms')->get();
        if ($context->isMicrosite() && $links->isEmpty()) {
            return null;
        }
        $base = $context->isMicrosite() ? rtrim((string) app(MicrositeResolver::class)->urlFor($context->entity, $locale), '/') : '';
        app(Seo::class)->set(['title' => __('links::links.plural'), 'path' => $base.'/'.Link::PATH]);

        $view = request()->query('view');
        $layout = is_string($view) && in_array($view, self::VIEWS, true) ? $view : (app(LayoutResolver::class)->for('links.listing') ?? 'tiles');

        return response()->view('links::index', ['groups' => self::grouped($links), 'layout' => in_array($layout, self::VIEWS, true) ? $layout : 'tiles']);
    }

    /** Visible links of a place in their order. */
    public static function shown(string $contextKey): Builder
    {
        return app(RelationEngine::class)->for($contextKey)->content(Link::class)->query()->reorder()->ordered();
    }

    /**
     * @param  Collection<int, Link>  $links
     * @return list<array{group: ?Term, links: Collection<int, Link>}>
     */
    public static function grouped(Collection $links): array
    {
        $order = array_keys(app(Vocabularies::class)->options(Link::GROUP));
        $terms = Term::query()->whereKey($order)->get()->keyBy('id');
        $vocabulary = app(Vocabularies::class)->find(Link::GROUP)?->id;
        $groupOf = fn (Link $l): ?int => $l->terms->firstWhere('vocabulary_id', $vocabulary)?->id;

        $out = [];
        foreach ($order as $id) {
            $in = $links->filter(fn (Link $l): bool => $groupOf($l) === $id)->values();
            if ($in->isNotEmpty()) {
                $out[] = ['group' => $terms[$id], 'links' => $in];
            }
        }
        $none = $links->filter(fn (Link $l): bool => $groupOf($l) === null)->values();
        if ($none->isNotEmpty()) {
            $out[] = ['group' => null, 'links' => $none];
        }

        return $out;
    }
}
