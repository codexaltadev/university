<?php

namespace Modules\Csr\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Modules\Csr\Models\Activity;
use Platform\Context\MicrositeResolver;
use Platform\Context\SiteContext;
use Platform\Relations\RelationEngine;
use Platform\Seo\Seo;
use Platform\Taxonomy\Term;
use Platform\Theme\LayoutResolver;

/**
 * /csr (FR-CSR-02): the top categories as cards (picture, description, how many), or the activities
 * when there are no categories; /csr/category/{slug}: its sub categories and its activities (sub
 * categories included); /csr/{slug}: an activity. Also under a microsite. Layout card / grid / timeline.
 */
class CsrWebsite
{
    public const VIEWS = ['cards', 'grid', 'timeline'];

    public function __construct(protected Categories $categories) {}

    public function respond(string $rest, SiteContext $context, string $locale): Response|RedirectResponse|null
    {
        $rest = trim($rest, '/');
        if ($rest !== Activity::PATH && ! Str::startsWith($rest, Activity::PATH.'/')) {
            return null;
        }
        $base = $context->isMicrosite() ? rtrim((string) app(MicrositeResolver::class)->urlFor($context->entity, $locale), '/') : '';
        if ($context->isMicrosite() && ! self::shown($context)->exists()) {
            return null;
        }
        $tail = trim(Str::after($rest, Activity::PATH), '/');

        if ($tail === '' || Str::startsWith($tail, 'category/')) {
            $all = $this->categories->all();
            $category = null;
            if ($tail !== '') {
                $category = $all->first(fn (Term $t): bool => $t->slug($locale) === Str::after($tail, 'category/'));
                if ($category === null) {
                    return null;
                }
            }
            $children = $all->where('parent_id', $category?->id)->values();
            app(Seo::class)->set(['title' => $category ? $category->name.' — '.__('csr::csr.plural') : __('csr::csr.plural'), 'path' => '/'.$rest]);
            $activities = ($category || $children->isEmpty())
                ? self::shown($context)->when($category, fn (Builder $q) => $q->whereHas('terms', fn (Builder $t) => $t->whereIn('core_terms.id', [$category->id, ...$category->descendantIds()])))->paginate(12)->withQueryString()
                : null;
            $view = request()->query('view');
            $layout = is_string($view) && in_array($view, self::VIEWS, true) ? $view : (app(LayoutResolver::class)->for('csr.listing') ?? 'cards');

            return response()->view('csr::index', [
                'category' => $category,
                'children' => $children->map(fn (Term $t): array => ['term' => $t, 'image' => $this->categories->image($t)])->all(),
                'activities' => $activities,
                'layout' => in_array($layout, self::VIEWS, true) ? $layout : 'cards',
                'base' => $base,
            ]);
        }

        if (str_contains($tail, '/') || ! ($found = Activity::findBySlug($tail, $locale))) {
            return null;
        }
        [$activity, $current] = $found;
        if (! self::shown($context)->whereKey($activity->getKey())->exists()) {
            return null;
        }
        if (! $current) {
            return redirect(Seo::localized($base.'/'.Activity::PATH.'/'.$activity->slug($locale), $locale), 301);
        }
        app(Seo::class)->forRecord($activity);

        return response()->view('csr::show', ['activity' => $activity, 'gallery' => $activity->mediaIn('gallery'), 'base' => $base]);
    }

    public static function shown(SiteContext|string $context): Builder
    {
        $key = is_string($context) ? $context : ($context->isMicrosite() ? $context->key() : RelationEngine::SITE_KEY);

        return app(RelationEngine::class)->for($key)->content(Activity::class)->includeDescendants($key !== RelationEngine::SITE_KEY)->query()->newest();
    }
}
