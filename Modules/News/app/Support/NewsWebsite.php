<?php

namespace Modules\News\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\News\Models\Post;
use Platform\Context\MicrositeResolver;
use Platform\Context\SiteContext;
use Platform\Relations\RelationEngine;
use Platform\Seo\Seo;
use Platform\Taxonomy\Term;
use Platform\Taxonomy\Vocabularies;
use Platform\Theme\LayoutResolver;

/**
 * /news (FR-NEWS-03/05): the posts published on the main site; /{entity}/news those shown in the
 * entity or below it (AC-NEWS-01). Filters ?category= (with sub-categories), ?tag=, ?year= &
 * ?month= (archive), ?q=; layouts grid / list / cards / magazine. /news/{slug} (and under a
 * microsite): only where the post is shown — standard or full-width, gallery, video, related
 * posts of the same place.
 */
class NewsWebsite
{
    public const VIEWS = ['grid', 'list', 'cards', 'magazine'];

    public const DETAIL = ['standard', 'full_width'];

    public function respond(string $rest, SiteContext $context, string $locale): Response|RedirectResponse|null
    {
        $rest = trim($rest, '/');
        if ($rest !== Post::PATH && ! Str::startsWith($rest, Post::PATH.'/')) {
            return null;
        }

        $base = $context->isMicrosite() ? rtrim((string) app(MicrositeResolver::class)->urlFor($context->entity, $locale), '/') : '';

        if ($rest === Post::PATH) {
            return $this->listing($context, $base, $locale);
        }

        $slug = Str::after($rest, Post::PATH.'/');
        if (str_contains($slug, '/') || ! ($found = Post::findBySlug($slug, $locale))) {
            return null;
        }
        [$post, $current] = $found;
        if (! $this->shown($context)->whereKey($post->getKey())->exists()) {
            return null;   // not shown in this place (AC-NEWS-01), not active or scheduled (AC-NEWS-02)
        }
        if (! $current) {
            return redirect(Seo::localized($base.'/'.Post::PATH.'/'.$post->slug($locale), $locale), 301);   // FR-GEN-32
        }

        $post->countView();
        app(Seo::class)->forRecord($post);
        $layout = app(LayoutResolver::class)->for('news.detail') ?? 'standard';

        return response()->view('news::show', [
            'post' => $post,
            'related' => $this->shown($context)->whereKeyNot($post->getKey())->newest()->limit(4)->get(),
            'layout' => in_array($layout, self::DETAIL, true) ? $layout : 'standard',
            'base' => $base,
        ]);
    }

    /** Visible posts of this place: the main site's, or an entity's and those below it. */
    protected function shown(SiteContext $context): Builder
    {
        return app(RelationEngine::class)->for($context->isMicrosite() ? $context->key() : RelationEngine::SITE_KEY)
            ->content(Post::class)->includeDescendants($context->isMicrosite())->query();
    }

    protected function listing(SiteContext $context, string $base, string $locale): ?Response
    {
        $request = request();
        $all = $this->shown($context);
        if ($context->isMicrosite() && ! (clone $all)->exists()) {
            return null;
        }
        app(Seo::class)->set(['title' => __('news::news.plural'), 'path' => $base.'/'.Post::PATH]);

        $categories = $this->terms(Post::CATEGORY);
        $category = $this->bySlug($categories, $request->query('category'), $locale);
        $tag = is_string($request->query('tag')) && $request->query('tag') !== ''
            ? Term::query()->where('vocabulary_id', app(Vocabularies::class)->find(Vocabularies::SHARED_TAGS)->id)->get()->first(fn (Term $t): bool => $t->slug($locale) === $request->query('tag'))
            : null;
        $date = 'COALESCE(news_posts.active_at, news_posts.created_at)';

        $posts = (clone $all)
            ->when($category, fn (Builder $query) => $query->whereHas('terms', fn (Builder $t) => $t->whereIn('core_terms.id', [$category->id, ...$category->descendantIds()])))
            ->when($tag, fn (Builder $query) => $query->withTerm($tag))
            ->when($request->filled('year'), fn (Builder $query) => $query->whereRaw("YEAR({$date}) = ?", [(int) $request->query('year')]))
            ->when($request->filled('year') && $request->filled('month'), fn (Builder $query) => $query->whereRaw("MONTH({$date}) = ?", [(int) $request->query('month')]))
            ->when(filled($search = trim((string) $request->query('q'))), fn (Builder $query) => $query->where(function (Builder $w) use ($search): void {
                $like = '%'.mb_strtolower(addcslashes($search, '%_\\')).'%';
                foreach (['title', 'subtitle', 'excerpt'] as $column) {
                    $w->orWhereRaw('LOWER(CAST(news_posts.'.$column.' AS CHAR)) LIKE ?', [$like]);
                }
            }))
            ->with(['terms', 'mediaUsages'])
            ->newest()
            ->paginate((int) (settings('news.per_page') ?: 12))->withQueryString();

        $wanted = $request->query('view');
        $layout = is_string($wanted) && in_array($wanted, self::VIEWS, true) ? $wanted : (app(LayoutResolver::class)->for('news.listing') ?? 'grid');

        return response()->view('news::index', [
            'posts' => $posts,
            'categories' => $categories->whereNull('parent_id')->values(),
            'category' => $category,
            'tag' => $tag,
            'archive' => $this->archive(clone $all),
            'layout' => in_array($layout, self::VIEWS, true) ? $layout : 'grid',
            'base' => $base,
        ]);
    }

    /**
     * Year → months with how many posts, for this place (FR-NEWS-05).
     *
     * @return Collection<int, object{year: int, month: int, total: int}>
     */
    protected function archive(Builder $query): Collection
    {
        $date = 'COALESCE(news_posts.active_at, news_posts.created_at)';

        return $query->reorder()->selectRaw("YEAR({$date}) as year, MONTH({$date}) as month, COUNT(*) as total")
            ->groupByRaw("YEAR({$date}), MONTH({$date})")->orderByDesc('year')->orderByDesc('month')->limit(24)
            ->toBase()->get();
    }

    /** @return Collection<int, Term> in the vocabulary's order */
    protected function terms(string $vocabulary): Collection
    {
        $order = array_keys(app(Vocabularies::class)->options($vocabulary));

        return Term::query()->whereKey($order)->get()->sortBy(fn (Term $t): int => array_search($t->id, $order, true))->values();
    }

    protected function bySlug(Collection $terms, mixed $slug, string $locale): ?Term
    {
        return is_string($slug) && $slug !== '' ? $terms->first(fn (Term $t): bool => $t->slug($locale) === $slug) : null;
    }
}
