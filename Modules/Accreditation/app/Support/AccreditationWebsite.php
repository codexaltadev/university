<?php

namespace Modules\Accreditation\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\Accreditation\Models\Accreditation;
use Modules\Accreditation\Models\Ranking;
use Platform\Context\MicrositeResolver;
use Platform\Context\SiteContext;
use Platform\Relations\RelationEngine;
use Platform\Seo\Seo;
use Platform\Taxonomy\Vocabularies;

/**
 * /accreditations (FR-ACR-05): by category, an expired one hidden or marked (setting);
 * /accreditations/{slug}: one with its certificate; /rankings (FR-ACR-04): each result's latest year
 * first, then each agency's years as a trend. Also under a microsite (a program's, a faculty's …).
 */
class AccreditationWebsite
{
    public function respond(string $rest, SiteContext $context, string $locale): Response|RedirectResponse|null
    {
        $rest = trim($rest, '/');
        $base = $context->isMicrosite() ? rtrim((string) app(MicrositeResolver::class)->urlFor($context->entity, $locale), '/') : '';

        if ($rest === Ranking::PATH) {
            $rankings = self::rankings($context)->get();
            if ($context->isMicrosite() && $rankings->isEmpty()) {
                return null;
            }
            app(Seo::class)->set(['title' => __('accreditation::accreditation.rankings'), 'path' => '/'.$rest]);

            return response()->view('accreditation::rankings', ['latest' => self::latest($rankings), 'trends' => self::trends($rankings), 'all' => $rankings, 'base' => $base]);
        }

        if ($rest !== Accreditation::PATH && ! Str::startsWith($rest, Accreditation::PATH.'/')) {
            return null;
        }
        $tail = trim(Str::after($rest, Accreditation::PATH), '/');
        if ($tail === '') {
            $items = self::accreditations($context)->with('terms')->get();
            if ($context->isMicrosite() && $items->isEmpty()) {
                return null;
            }
            app(Seo::class)->set(['title' => __('accreditation::accreditation.plural'), 'path' => '/'.$rest]);

            return response()->view('accreditation::index', ['groups' => self::byCategory($items), 'base' => $base]);
        }

        if (str_contains($tail, '/') || ! ($found = Accreditation::findBySlug($tail, $locale))) {
            return null;
        }
        [$accreditation, $current] = $found;
        if (! self::accreditations($context)->whereKey($accreditation->getKey())->exists()) {
            return null;
        }
        if (! $current) {
            return redirect(Seo::localized($base.'/'.Accreditation::PATH.'/'.$accreditation->slug($locale), $locale), 301);
        }
        app(Seo::class)->forRecord($accreditation);

        return response()->view('accreditation::show', ['accreditation' => $accreditation, 'base' => $base]);
    }

    /** The place's active accreditations in their order; the expired left out when the setting says "hide". */
    public static function accreditations(SiteContext|string $context): Builder
    {
        return app(RelationEngine::class)->for(self::key($context))->content(Accreditation::class)->query()
            ->when(settings('accreditation.expired', 'hide') === 'hide', fn (Builder $q) => $q->notExpired())
            ->reorder()->ordered();
    }

    public static function rankings(SiteContext|string $context): Builder
    {
        return app(RelationEngine::class)->for(self::key($context))->content(Ranking::class)->query()->newest();
    }

    /** @return list<array{term: mixed, items: Collection}> in the vocabulary's order, uncategorised last */
    public static function byCategory(EloquentCollection $items): array
    {
        $order = array_keys(app(Vocabularies::class)->options(Accreditation::CATEGORY));
        $groups = $items->groupBy(fn (Accreditation $a): int => (int) $a->termsIn(Accreditation::CATEGORY)->first()?->id);

        return $groups->sortBy(fn ($g, int $id): int => $id === 0 ? PHP_INT_MAX : (int) array_search($id, $order, true))
            ->map(fn (Collection $g, int $id): array => ['term' => $id ? $g->first()->termsIn(Accreditation::CATEGORY)->first() : null, 'items' => $g->values()])
            ->values()->all();
    }

    /** Each result's latest year (agency + scope + subject), newest first. */
    public static function latest(Collection $rankings): Collection
    {
        return $rankings->groupBy(fn (Ranking $r): string => $r->agency_key.'|'.$r->scope.'|'.$r->subject_key)
            ->map(fn (Collection $g): Ranking => $g->sortByDesc('year')->first())
            ->sortBy([['year', 'desc'], ['sort_order', 'asc'], ['rank_value', 'asc']])->values();
    }

    /** @return Collection<string, array{name: string, key: string, rows: Collection}> agency → its results by year (newest first) */
    public static function trends(Collection $rankings): Collection
    {
        return $rankings->groupBy('agency_key')
            ->filter(fn (Collection $g): bool => $g->pluck('year')->unique()->count() > 1)
            ->map(fn (Collection $g, string $key): array => ['key' => $key, 'name' => (string) $g->first()->agency_name, 'rows' => $g->sortBy([['year', 'desc'], ['scope', 'asc']])->values()]);
    }

    protected static function key(SiteContext|string $context): string
    {
        return is_string($context) ? $context : ($context->isMicrosite() ? $context->key() : RelationEngine::SITE_KEY);
    }
}
