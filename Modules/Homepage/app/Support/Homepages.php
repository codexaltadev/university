<?php

namespace Modules\Homepage\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Homepage\Models\HomeSection;
use Modules\Homepage\Models\Layout;
use Modules\Homepage\Sections;
use Platform\Homepage\Section;
use Platform\Homepage\SectionContext;
use Platform\Registry\Registry;
use Platform\Scope\UserScopeFilter;

/**
 * Homepages (C-10): the section catalogue (core + modules), an owner's layout, and
 * the data the theme renders. Section data is cached ten minutes and refreshed when
 * content changes (SAD Part B §9).
 *
 * Website section: ['key', 'view', 'heading', 'subheading', 'settings', 'items'].
 * No (active) layout → null: the theme's default homepage (FR-HOME-08).
 */
class Homepages
{
    public const VERSION_KEY = 'homepage:version';

    public const CORE = [
        Sections\HeroSection::class, Sections\StatisticsSection::class, Sections\RichTextSection::class,
        Sections\ImageTextSection::class, Sections\CallToActionSection::class, Sections\CustomHtmlSection::class,
        Sections\SpacerSection::class,
    ];

    /** @return array<string, class-string<Section>> key => section, of active modules */
    public function catalogue(): array
    {
        $registered = array_column(app(Registry::class)->all('homepageSection'), 'class');

        return collect([...self::CORE, ...$registered])->mapWithKeys(fn (string $class): array => [$class::key() => $class])->all();
    }

    /** @return array<string, array<string, string>> module => [key => label], for the "add section" list */
    public function catalogueOptions(): array
    {
        return collect($this->catalogue())
            ->groupBy(fn (string $class, string $key): string => Str::headline(Str::before($key, '.')), preserveKeys: true)
            ->map(fn ($group): array => $group->map(fn (string $class): string => $class::label())->all())
            ->all();
    }

    public function own(string $ownerKey): ?Layout
    {
        return Layout::query()->withoutGlobalScope(UserScopeFilter::class)->ownedBy($ownerKey)->first();
    }

    /** Own homepage: empty, or a copy of another one (FR-HOME-10). */
    public function create(string $ownerKey, ?Layout $copyOf = null): Layout
    {
        return DB::transaction(function () use ($ownerKey, $copyOf): Layout {
            $layout = (new Layout(['is_active' => true]))->setOwnerKey($ownerKey);
            $layout->save();

            foreach ($copyOf?->sections()->get() ?? [] as $section) {
                $layout->sections()->create($section->only(['section_type', 'settings', 'sort_order', 'visible_from', 'visible_until', 'is_active'])
                    + ['heading' => $section->getTranslations('heading'), 'subheading' => $section->getTranslations('subheading')]);
            }

            static::flush();

            return $layout;
        });
    }

    /** Why the website hides a section, if it does (FR-HOME-06/09). */
    public function problem(HomeSection $section): ?string
    {
        return match (true) {
            ! $section->is_active => 'inactive',
            ! isset($this->catalogue()[$section->section_type]) => 'module_off',
            ! $section->isScheduledNow() => 'scheduled',
            default => null,
        };
    }

    /**
     * The homepage of an owner for the website.
     *
     * @return list<array<string, mixed>>|null null = theme default
     */
    public function forOwner(string $ownerKey, ?string $locale = null): ?array
    {
        $layout = $this->own($ownerKey);

        if ($layout === null || ! $layout->is_active) {
            return null;
        }

        $locale ??= app()->getLocale();
        $catalogue = $this->catalogue();

        return $layout->sections()->get()
            ->filter(fn (HomeSection $section): bool => $this->problem($section) === null)
            ->map(function (HomeSection $section) use ($catalogue, $ownerKey, $locale): array {
                $class = $catalogue[$section->section_type];
                $version = Cache::get(self::VERSION_KEY, 0);
                $ctx = new SectionContext($ownerKey, (array) $section->settings, $locale);

                return [
                    'key' => $section->section_type,
                    'view' => $class::view(),
                    'heading' => $section->getTranslation('heading', $locale, true) ?: null,
                    'subheading' => $section->getTranslation('subheading', $locale, true) ?: null,
                    'settings' => (array) $section->settings,
                    'items' => Cache::remember("homepage:{$version}:{$section->id}:{$locale}", 600,
                        fn () => UserScopeFilter::withoutScope(fn () => app($class)->items($ctx))),
                ];
            })
            ->values()->all();
    }

    public static function flush(): void
    {
        Cache::forever(self::VERSION_KEY, Cache::get(self::VERSION_KEY, 0) + 1);
    }
}
