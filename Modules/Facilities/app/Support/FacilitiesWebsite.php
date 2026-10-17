<?php

namespace Modules\Facilities\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\Facilities\Models\Facility;
use Platform\Context\MicrositeResolver;
use Platform\Context\SiteContext;
use Platform\Relations\RelationEngine;
use Platform\Seo\Seo;
use Platform\Taxonomy\Term;
use Platform\Taxonomy\Vocabularies;
use Platform\Theme\LayoutResolver;

/**
 * /facilities and /{entity}/facilities (FR-FCL-02): the place's facilities in their order, by
 * category (?category=); icon grid / cards / list. /facilities/{slug}: a facility with text.
 */
class FacilitiesWebsite
{
    public const VIEWS = ['icons', 'cards', 'list'];

    public function respond(string $rest, SiteContext $context, string $locale): Response|RedirectResponse|null
    {
        $rest = trim($rest, '/');
        if ($rest !== Facility::PATH && ! Str::startsWith($rest, Facility::PATH.'/')) {
            return null;
        }
        $base = $context->isMicrosite() ? rtrim((string) app(MicrositeResolver::class)->urlFor($context->entity, $locale), '/') : '';

        if ($rest === Facility::PATH) {
            if ($context->isMicrosite() && ! self::shown($context)->exists()) {
                return null;
            }
            app(Seo::class)->set(['title' => __('facilities::facilities.plural'), 'path' => $base.'/'.Facility::PATH]);
            $terms = $this->terms();
            $wanted = request()->query('category');
            $category = is_string($wanted) && $wanted !== '' ? $terms->first(fn (Term $t): bool => $t->slug($locale) === $wanted) : null;
            $view = request()->query('view');
            $layout = is_string($view) && in_array($view, self::VIEWS, true) ? $view : (app(LayoutResolver::class)->for('facilities.listing') ?? 'icons');

            return response()->view('facilities::index', [
                'facilities' => self::shown($context)->when($category, fn (Builder $q) => $q->withTerm($category))->get(),
                'categories' => $terms,
                'category' => $category,
                'layout' => in_array($layout, self::VIEWS, true) ? $layout : 'icons',
                'base' => $base,
            ]);
        }

        $slug = Str::after($rest, Facility::PATH.'/');
        if (str_contains($slug, '/') || ! ($found = Facility::findBySlug($slug, $locale))) {
            return null;
        }
        [$facility, $current] = $found;
        if (! $facility->hasPage() || ! self::shown($context)->whereKey($facility->getKey())->exists()) {
            return null;
        }
        if (! $current) {
            return redirect(Seo::localized($base.'/'.Facility::PATH.'/'.$facility->slug($locale), $locale), 301);
        }
        app(Seo::class)->forRecord($facility);

        return response()->view('facilities::show', ['facility' => $facility, 'gallery' => $facility->mediaIn('gallery'), 'base' => $base]);
    }

    public static function shown(SiteContext|string $context): Builder
    {
        $key = is_string($context) ? $context : ($context->isMicrosite() ? $context->key() : RelationEngine::SITE_KEY);

        return app(RelationEngine::class)->for($key)->content(Facility::class)->query()->reorder()->ordered();
    }

    /** @return Collection<int, Term> */
    protected function terms(): Collection
    {
        $order = array_keys(app(Vocabularies::class)->options(Facility::CATEGORY));

        return Term::query()->whereKey($order)->get()->sortBy(fn (Term $t): int => array_search($t->id, $order, true))->values();
    }
}
