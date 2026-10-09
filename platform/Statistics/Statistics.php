<?php

namespace Platform\Statistics;

use Illuminate\Support\Facades\Cache;
use Platform\Localization\Numerals;
use Platform\Registry\Registry;
use Platform\Scope\UserScopeFilter;

/**
 * Statistics engine (C-20, SAD Part B §7): metric providers of active modules, values
 * per context (cached an hour, refreshed when content changes — FR-STAT-05), display
 * with override, rounding, prefix / suffix and the language's digits (FR-STAT-06/09).
 *
 * Website item: ['label', 'value', 'display', 'icon', 'as_of'].
 */
class Statistics
{
    public const VERSION_KEY = 'metric:version';

    /** @return array<string, class-string<MetricProvider>> key => provider, of active modules */
    public function providers(): array
    {
        return collect(app(Registry::class)->all('metric'))->mapWithKeys(fn (array $e): array => [$e['key'] => $e['class']])->all();
    }

    /** @return array<string, string> key => label */
    public function metricLabels(): array
    {
        return collect($this->providers())->map(fn (string $class): string => app($class)->label())->all();
    }

    public function metric(string $key, StatContext $ctx, array $params = []): ?int
    {
        $class = $this->providers()[$key] ?? null;

        if ($class === null) {
            return null;
        }

        $version = Cache::get(self::VERSION_KEY, 0);
        $hash = md5(json_encode($params));

        // Counting is for the website: no admin scope filter on the providers' queries
        return Cache::remember("metric:{$version}:{$key}:{$ctx->ownerKey}:{$hash}", 3600,
            fn (): int => UserScopeFilter::withoutScope(fn (): int => app($class)->count($ctx, $params)));
    }

    /** Content changed: every counter is counted again on next use (FR-STAT-05). */
    public static function flush(): void
    {
        Cache::forever(self::VERSION_KEY, Cache::get(self::VERSION_KEY, 0) + 1);
    }

    /** override ?? manual ?? metric (SAD Part B §7.3) */
    public function value(StatItem $item, StatContext $ctx): ?int
    {
        if ($item->override_value !== null) {
            return $item->override_value;
        }

        return $item->source === 'metric'
            ? ($item->metric_key ? $this->metric($item->metric_key, $ctx, (array) $item->metric_params) : null)
            : $item->manual_value;
    }

    /** Why the website hides this counter, if it does (FR-STAT-04). */
    public function problem(StatItem $item): ?string
    {
        return match (true) {
            ! $item->is_active => 'inactive',
            $item->source === 'metric' && ! isset($this->providers()[$item->metric_key]) && $item->override_value === null => 'module_off',
            $item->source === 'manual' && $item->manual_value === null && $item->override_value === null => 'no_value',
            default => null,
        };
    }

    /** "1,234" → "1,200+" with prefix / suffix, in the language's digits. */
    public function display(StatItem $item, int $value, ?string $locale = null): string
    {
        [$shown, $plus] = match ($item->rounding) {
            'hundred' => [intdiv($value, 100) * 100, $value >= 100],
            'thousand' => [intdiv($value, 1000) * 1000, $value >= 1000],
            'plus' => [$value, true],
            default => [$value, false],
        };

        $text = ($item->prefix ?? '').number_format($shown).($plus ? '+' : '').($item->suffix ?? '');

        return Numerals::localize($text, $locale);
    }

    /** @return list<array<string, mixed>> visible counters of a set, in order */
    public function forSet(StatSet $set, ?string $locale = null): array
    {
        $ctx = StatContext::of($set->ownerKey());
        $locale ??= app()->getLocale();

        return $set->items()->get()
            ->filter(fn (StatItem $item): bool => $this->problem($item) === null)
            ->map(function (StatItem $item) use ($ctx, $locale): ?array {
                $value = $this->value($item, $ctx);

                return $value === null ? null : [
                    'label' => $item->getTranslation('label', $locale),
                    'value' => $value,
                    'display' => $this->display($item, $value, $locale),
                    'icon' => $item->icon,
                    'as_of' => $item->as_of?->toDateString(),
                ];
            })
            ->filter()->values()->all();
    }

    /** An owner's set; none → [] and the theme hides the section or shows its default (FR-STAT-08). */
    public function set(string $ownerKey, string $key = 'default'): ?StatSet
    {
        return StatSet::query()->withoutGlobalScope(UserScopeFilter::class)
            ->ownedBy($ownerKey)->where('key', $key)->where('is_active', true)->first();
    }

    /** @return list<array<string, mixed>> */
    public function forOwner(string $ownerKey, string $key = 'default', ?string $locale = null): array
    {
        $set = $this->set($ownerKey, $key);

        return $set ? $this->forSet($set, $locale) : [];
    }
}
