<?php

namespace Modules\Gallery\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\Gallery\Models\Album;
use Platform\Context\MicrositeResolver;
use Platform\Context\SiteContext;
use Platform\Relations\RelationEngine;
use Platform\Seo\Seo;
use Platform\Taxonomy\Term;
use Platform\Taxonomy\Vocabularies;
use Platform\Theme\LayoutResolver;

/**
 * /gallery and /{entity}/gallery (FR-GAL-05): the place's albums, newest first; ?category=
 * (sub-categories included), ?kind=photos|videos, cards / grid. /gallery/{slug} (also under a
 * microsite, only where the album is shown): pictures as masonry / grid / carousel with a
 * lightbox, uploaded and link videos.
 */
class GalleryWebsite
{
    public const LISTINGS = ['cards', 'grid'];

    public const ALBUM_VIEWS = ['masonry', 'grid', 'carousel'];

    public function respond(string $rest, SiteContext $context, string $locale): Response|RedirectResponse|null
    {
        $rest = trim($rest, '/');
        if ($rest !== Album::PATH && ! Str::startsWith($rest, Album::PATH.'/')) {
            return null;
        }
        $base = $context->isMicrosite() ? rtrim((string) app(MicrositeResolver::class)->urlFor($context->entity, $locale), '/') : '';

        if ($rest === Album::PATH) {
            return $this->listing($context, $base, $locale);
        }

        $slug = Str::after($rest, Album::PATH.'/');
        if (str_contains($slug, '/') || ! ($found = Album::findBySlug($slug, $locale))) {
            return null;
        }
        [$album, $current] = $found;
        if (! self::shown($context)->whereKey($album->getKey())->exists()) {
            return null;
        }
        if (! $current) {
            return redirect(Seo::localized($base.'/'.Album::PATH.'/'.$album->slug($locale), $locale), 301);
        }

        $album->countView();
        app(Seo::class)->forRecord($album);
        $view = request()->query('view');
        $layout = is_string($view) && in_array($view, self::ALBUM_VIEWS, true) ? $view : (app(LayoutResolver::class)->for('gallery.album') ?? 'masonry');

        return response()->view('gallery::show', [
            'album' => $album,
            'photos' => $album->entries('image'),
            'uploads' => $album->entries('video'),
            'videos' => $album->videos()->shown()->get(),
            'layout' => in_array($layout, self::ALBUM_VIEWS, true) ? $layout : 'masonry',
            'base' => $base,
        ]);
    }

    /** Visible albums of a place: the main site's, or an entity's and those below it. */
    public static function shown(SiteContext|string $context, ?bool $descendants = null): Builder
    {
        $key = is_string($context) ? $context : ($context->isMicrosite() ? $context->key() : RelationEngine::SITE_KEY);
        $descendants ??= $key !== RelationEngine::SITE_KEY;

        return app(RelationEngine::class)->for($key)->content(Album::class)->includeDescendants($descendants)->query()->newest();
    }

    protected function listing(SiteContext $context, string $base, string $locale): ?Response
    {
        $request = request();
        if ($context->isMicrosite() && ! self::shown($context)->exists()) {
            return null;
        }
        app(Seo::class)->set(['title' => __('gallery::gallery.plural'), 'path' => $base.'/'.Album::PATH]);

        $categories = $this->terms();
        $wanted = $request->query('category');
        $category = is_string($wanted) && $wanted !== '' ? $categories->first(fn (Term $t): bool => $t->slug($locale) === $wanted) : null;
        $kind = in_array($request->query('kind'), ['photos', 'videos'], true) ? (string) $request->query('kind') : null;

        $albums = self::shown($context)
            ->when($category, fn (Builder $query) => $query->whereHas('terms', fn (Builder $t) => $t->whereIn('core_terms.id', [$category->id, ...$category->descendantIds()])))
            ->when($kind === 'photos', fn (Builder $query) => $query->whereHas('mediaUsages', fn (Builder $u) => $u->where('field', Album::ITEMS)->whereHas('item', fn (Builder $i) => $i->where('kind', 'image'))))
            ->when($kind === 'videos', fn (Builder $query) => $query->where(fn (Builder $w) => $w->whereHas('videos', fn (Builder $v) => $v->where('is_active', true))
                ->orWhereHas('mediaUsages', fn (Builder $u) => $u->where('field', Album::ITEMS)->whereHas('item', fn (Builder $i) => $i->where('kind', 'video')))))
            ->with('videos')
            ->paginate(12)->withQueryString();

        $view = $request->query('view');
        $layout = is_string($view) && in_array($view, self::LISTINGS, true) ? $view : (app(LayoutResolver::class)->for('gallery.listing') ?? 'cards');

        return response()->view('gallery::index', [
            'albums' => $albums,
            'categories' => $categories->whereNull('parent_id')->values(),
            'category' => $category,
            'kind' => $kind,
            'layout' => in_array($layout, self::LISTINGS, true) ? $layout : 'cards',
            'base' => $base,
        ]);
    }

    /** @return Collection<int, Term> in the vocabulary's order */
    protected function terms(): Collection
    {
        $order = array_keys(app(Vocabularies::class)->options(Album::CATEGORY));

        return Term::query()->whereKey($order)->get()->sortBy(fn (Term $t): int => array_search($t->id, $order, true))->values();
    }
}
