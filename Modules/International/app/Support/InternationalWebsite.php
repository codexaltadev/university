<?php

namespace Modules\International\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Modules\International\Models\Conference;
use Modules\International\Models\Country;
use Modules\International\Models\Institution;
use Modules\International\Models\Visitor;
use Platform\Context\MicrositeResolver;
use Platform\Context\SiteContext;
use Platform\Relations\RelationEngine;
use Platform\Seo\Seo;
use Platform\Theme\LayoutResolver;

/**
 * /international: the three lists with their counts; /international/partners (FR-INTL-02): by
 * country, agreement, search; logo grid / table / countries; the expired hidden or marked (setting);
 * /international/conferences (+ /{slug}, FR-INTL-05); /international/visitors (FR-INTL-06).
 * Also under a microsite.
 */
class InternationalWebsite
{
    public const ROOT = 'international';

    public const VIEWS = ['grid', 'table', 'countries'];

    public function respond(string $rest, SiteContext $context, string $locale): Response|RedirectResponse|null
    {
        $rest = trim($rest, '/');
        if ($rest !== self::ROOT && ! Str::startsWith($rest, self::ROOT.'/')) {
            return null;
        }
        $base = $context->isMicrosite() ? rtrim((string) app(MicrositeResolver::class)->urlFor($context->entity, $locale), '/') : '';
        $tail = trim(Str::after($rest, self::ROOT), '/');

        return match (true) {
            $tail === '' => $this->home($context, $base),
            $tail === 'partners' => $this->partnersPage($context, $base),
            $tail === 'visitors' => $this->visitorsPage($context, $base),
            $tail === 'conferences' => $this->conferencesPage($context, $base),
            Str::startsWith($tail, 'conferences/') => $this->conference(Str::after($tail, 'conferences/'), $context, $base, $locale),
            default => null,
        };
    }

    protected function home(SiteContext $context, string $base): ?Response
    {
        $counts = [
            'partners' => self::institutions($context)->count(),
            'countries' => self::institutions($context)->distinct()->count('country_id'),
            'conferences' => self::conferences($context)->count(),
            'visitors' => self::visitors($context)->count(),
        ];
        if ($context->isMicrosite() && array_sum($counts) === 0) {
            return null;
        }
        app(Seo::class)->set(['title' => __('international::international.title'), 'path' => '/'.self::ROOT]);

        return response()->view('international::home', [
            'counts' => $counts,
            'featured' => app(RelationEngine::class)->for(self::key($context))->content(Institution::class)->highlighted(12),
            'base' => $base,
        ]);
    }

    protected function partnersPage(SiteContext $context, string $base): ?Response
    {
        $all = self::institutions($context);
        if ($context->isMicrosite() && ! (clone $all)->exists()) {
            return null;
        }
        $countries = Country::query()->whereIn('id', (clone $all)->select('country_id'))->get()->sortBy(fn (Country $c): string => (string) $c->name)->values();
        $country = $countries->first(fn (Country $c): bool => $c->iso2 === Str::upper((string) request()->query('country')));
        $type = in_array(request()->query('type'), Institution::AGREEMENTS, true) ? request()->query('type') : null;
        $q = trim((string) request()->query('q'));
        $view = request()->query('view');
        $layout = is_string($view) && in_array($view, self::VIEWS, true) ? $view : (app(LayoutResolver::class)->for('international.partners') ?? 'grid');
        $layout = in_array($layout, self::VIEWS, true) ? $layout : 'grid';

        $query = (clone $all)->with('country')
            ->when($country, fn (Builder $b) => $b->where('country_id', $country->id))
            ->when($type, fn (Builder $b) => $b->where('agreement_type', $type))
            ->when($q !== '', fn (Builder $b) => $b->whereRaw('LOWER(CAST('.$b->qualifyColumn('name').' AS CHAR)) LIKE ?', ['%'.addcslashes(mb_strtolower($q), '%_\\').'%']));   // JSON compares case-sensitively
        $perCountry = $layout === 'countries'
            ? (clone $query)->reorder()->selectRaw('country_id, COUNT(*) AS total')->groupBy('country_id')->pluck('total', 'country_id')
            : collect();
        app(Seo::class)->set(['title' => __('international::international.partners'), 'path' => '/'.self::ROOT.'/partners']);

        return response()->view('international::partners', [
            'partners' => $layout === 'countries' ? null : $query->paginate($layout === 'table' ? 50 : 48)->withQueryString(),
            'countries' => $countries,
            'perCountry' => $perCountry,
            'country' => $country,
            'type' => $type,
            'q' => $q,
            'layout' => $layout,
            'base' => $base,
        ]);
    }

    protected function conferencesPage(SiteContext $context, string $base): ?Response
    {
        $items = self::conferences($context)->get();
        if ($context->isMicrosite() && $items->isEmpty()) {
            return null;
        }
        app(Seo::class)->set(['title' => __('international::international.conferences'), 'path' => '/'.Conference::PATH]);
        [$upcoming, $past] = $items->partition(fn (Conference $c): bool => $c->isUpcoming());

        return response()->view('international::conferences', ['upcoming' => $upcoming->sortBy('starts_on')->values(), 'past' => $past->values(), 'base' => $base]);
    }

    protected function conference(string $slug, SiteContext $context, string $base, string $locale): Response|RedirectResponse|null
    {
        if (str_contains($slug, '/') || ! ($found = Conference::findBySlug($slug, $locale))) {
            return null;
        }
        [$conference, $current] = $found;
        if (! self::conferences($context)->whereKey($conference->getKey())->exists()) {
            return null;
        }
        if (! $current) {
            return redirect(Seo::localized($base.'/'.Conference::PATH.'/'.$conference->slug($locale), $locale), 301);
        }
        app(Seo::class)->forRecord($conference);

        return response()->view('international::conference', ['conference' => $conference, 'base' => $base]);
    }

    protected function visitorsPage(SiteContext $context, string $base): ?Response
    {
        $visitors = self::visitors($context)->with('country')->paginate(24)->withQueryString();
        if ($context->isMicrosite() && $visitors->isEmpty()) {
            return null;
        }
        app(Seo::class)->set(['title' => __('international::international.visitors'), 'path' => '/'.Visitor::PATH]);

        return response()->view('international::visitors', ['visitors' => $visitors, 'base' => $base]);
    }

    /** The place's partner institutions (active, in their order); the expired left out when the setting says "hide". */
    public static function institutions(SiteContext|string $context): Builder
    {
        return app(RelationEngine::class)->for(self::key($context))->content(Institution::class)->includeDescendants(self::key($context) !== RelationEngine::SITE_KEY)->query()
            ->when(settings('international.expired', 'hide') === 'hide', fn (Builder $q) => $q->notExpired())
            ->reorder()->ordered();
    }

    public static function conferences(SiteContext|string $context): Builder
    {
        return app(RelationEngine::class)->for(self::key($context))->content(Conference::class)->query()->newest();
    }

    public static function visitors(SiteContext|string $context): Builder
    {
        return app(RelationEngine::class)->for(self::key($context))->content(Visitor::class)->query()->newest();
    }

    protected static function key(SiteContext|string $context): string
    {
        return is_string($context) ? $context : ($context->isMicrosite() ? $context->key() : RelationEngine::SITE_KEY);
    }
}
