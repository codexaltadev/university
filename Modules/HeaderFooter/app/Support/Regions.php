<?php

namespace Modules\HeaderFooter\Support;

use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;
use Modules\HeaderFooter\Models\Region;
use Platform\Context\FallbackResolver;
use Platform\Context\MicrositeOwners;
use Platform\Context\SiteContext;
use Platform\Scope\UserScopeFilter;
use Platform\Theme\Themes;

/**
 * Top bar, header and footer for the website and the admin screen (C-09): the region
 * that applies in a context (own → parent … → main site), own regions, create / copy / delete.
 *
 * Website data: ['variant', 'config', 'copyright' (with this year), 'from'] or null (theme default).
 */
class Regions
{
    /** @return array<string, string> variant => label, of the active theme (FR-HF-01, theme.json "{region}_variants") */
    public static function variants(string $region): array
    {
        $variants = app(Themes::class)->variants($region) ?: config("headerfooter.variants.{$region}", ['default']);

        return collect($variants)
            ->mapWithKeys(fn (string $v): array => [$v => Lang::has("headerfooter::headerfooter.variants.{$v}") ? __("headerfooter::headerfooter.variants.{$v}") : Str::headline($v)])
            ->all();
    }

    /** @return array<string, mixed>|null */
    public function forContext(string $region, SiteContext $context, ?string $locale = null): ?array
    {
        $resolver = app(FallbackResolver::class);
        $found = $resolver->resolve($region, null, $context);

        if (! $found instanceof Region) {
            return null;
        }

        return [
            'variant' => $found->variant,
            'config' => (array) $found->config,
            'copyright' => $found->copyrightText($locale),
            'from' => $resolver->source($region, null, $context),
        ];
    }

    public function own(string $ownerKey, string $region): ?Region
    {
        return Region::query()->withoutGlobalScope(UserScopeFilter::class)->ownedBy($ownerKey)->where('region', $region)->first();
    }

    /** @return array{region: ?Region, from: ?string} what this owner uses now, and whose it is */
    public function current(string $ownerKey, string $region): array
    {
        $context = MicrositeOwners::context($ownerKey);
        $resolver = app(FallbackResolver::class);

        return ['region' => $resolver->resolve($region, null, $context), 'from' => $resolver->source($region, null, $context)];
    }

    /** Own region: the theme's first variant, or a copy of the one used now. */
    public function create(string $ownerKey, string $region, ?Region $copyOf = null): Region
    {
        $own = (new Region([
            'region' => $region,
            'variant' => $copyOf?->variant ?? array_key_first(static::variants($region)),
            'config' => $copyOf?->config ?? static::defaults($region),
        ]))->setOwnerKey($ownerKey);

        if ($copyOf) {
            $own->setTranslations('copyright', $copyOf->getTranslations('copyright'));
        }

        $own->save();
        app(FallbackResolver::class)->flush();

        return $own;
    }

    public function delete(Region $region): void
    {
        $region->delete();
        app(FallbackResolver::class)->flush();
    }

    /** @return array<string, mixed> */
    public static function defaults(string $region): array
    {
        return match ($region) {
            'topbar' => ['show_phone' => true, 'show_email' => true, 'show_quick_links' => true, 'show_language_switcher' => true, 'show_social' => true, 'show_login' => false],
            'header' => ['show_search' => true, 'sticky' => true, 'show_announcement_bar' => true],
            default => ['columns' => []],
        };
    }
}
