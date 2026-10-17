<?php

namespace Modules\Partners\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Modules\Partners\Models\Partner;
use Platform\Context\MicrositeResolver;
use Platform\Context\SiteContext;
use Platform\Relations\RelationEngine;
use Platform\Seo\Seo;
use Platform\Taxonomy\Term;
use Platform\Taxonomy\Vocabularies;

/**
 * /partners (FR-PRT-02): the place's partners by type (tabs, ?type=), discount partners apart;
 * /student-discounts: the current discounts by category with its icon (FR-PRT-04 hides the expired).
 * Also under a microsite (FR-PRT-03).
 */
class PartnersWebsite
{
    public function respond(string $rest, SiteContext $context, string $locale): ?Response
    {
        $rest = trim($rest, '/');
        if (! in_array($rest, [Partner::PATH, Partner::DISCOUNT_PATH], true)) {
            return null;
        }
        $base = $context->isMicrosite() ? rtrim((string) app(MicrositeResolver::class)->urlFor($context->entity, $locale), '/') : '';

        if ($rest === Partner::DISCOUNT_PATH) {
            $discounts = self::shown($context)->ofType('discount')->with('terms')->get();
            if ($context->isMicrosite() && $discounts->isEmpty()) {
                return null;
            }
            app(Seo::class)->set(['title' => __('partners::partners.discounts'), 'path' => '/'.$rest]);

            return response()->view('partners::discounts', ['groups' => self::byCategory($discounts), 'base' => $base]);
        }

        $partners = self::shown($context)->ofType(array_diff(Partner::TYPES, ['discount']))->get();
        $hasDiscounts = self::shown($context)->ofType('discount')->exists();
        if ($context->isMicrosite() && $partners->isEmpty() && ! $hasDiscounts) {
            return null;
        }
        $types = $partners->pluck('partner_type')->unique();
        $types = collect(Partner::TYPES)->filter(fn (string $t): bool => $types->contains($t))->values();
        $wanted = request()->query('type');
        $type = is_string($wanted) && $types->contains($wanted) ? $wanted : null;
        app(Seo::class)->set(['title' => __('partners::partners.plural'), 'path' => '/'.$rest]);

        return response()->view('partners::index', [
            'partners' => $type ? $partners->where('partner_type', $type)->values() : $partners,
            'types' => $types,
            'type' => $type,
            'hasDiscounts' => $hasDiscounts,
            'base' => $base,
        ]);
    }

    /** @return list<array{term: ?Term, partners: Collection<int, Partner>}> in the vocabulary's order, uncategorised last */
    public static function byCategory(Collection $discounts): array
    {
        $order = array_keys(app(Vocabularies::class)->options(Partner::CATEGORY));
        $groups = $discounts->groupBy(fn (Partner $p): int => (int) $p->termsIn(Partner::CATEGORY)->first()?->id);
        $terms = Term::query()->whereKey($groups->keys()->filter()->all())->get()->keyBy('id');

        return $groups->sortBy(fn ($items, int $id): int => $id === 0 ? PHP_INT_MAX : (int) array_search($id, $order, true))
            ->map(fn (Collection $items, int $id): array => ['term' => $terms->get($id), 'partners' => $items->values()])
            ->values()->all();
    }

    /** Active, current partners of the place, in their order. */
    public static function shown(SiteContext|string $context): Builder
    {
        $key = is_string($context) ? $context : ($context->isMicrosite() ? $context->key() : RelationEngine::SITE_KEY);

        return app(RelationEngine::class)->for($key)->content(Partner::class)->includeDescendants($key !== RelationEngine::SITE_KEY)->query()->current()->reorder()->ordered();
    }
}
