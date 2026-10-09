<?php

namespace Platform\Seo;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Platform\Concerns\HasSeo;
use Platform\Localization\Languages;
use Platform\Models\BaseModel;
use Platform\Registry\Registry;
use Platform\Scope\UserScopeFilter;

/**
 * sitemap.xml (FR-SEO-02, AC-SEO-02): the homepage, then what active modules
 * registered with $r->sitemap(Model::class, route: 'news.index') — published,
 * active records with a url($locale) method, each with its hreflang alternates.
 * Records marked noindex are left out. Cached until content changes.
 */
class Sitemap
{
    public const VERSION_KEY = 'sitemap:version';

    public const MAX_URLS = 50000;

    public function __construct(protected Registry $registry, protected Languages $languages) {}

    /** @return list<array{loc: string, lastmod: ?string, alternates: array<string, string>}> */
    public function entries(): array
    {
        $entries = [$this->entry('/', null, fn (string $code): string => '/')];

        foreach ($this->registry->all('sitemap') as $registered) {
            if (($route = $registered['route'] ?? null) && Route::has($route)) {
                $path = parse_url(route($route), PHP_URL_PATH) ?: '/';
                $entries[] = $this->entry($path, null, fn (string $code): string => $path);
            }

            $model = $registered['model'];

            if (! method_exists($model, 'url')) {
                continue;
            }

            foreach ($this->records($model) as $record) {
                if (count($entries) >= self::MAX_URLS) {
                    break 2;
                }

                $entries[] = $this->entry(
                    $record->url($this->languages->default()),
                    $record->updated_at?->toAtomString(),
                    fn (string $code): string => $record->url($code),
                );
            }
        }

        return collect($entries)->unique('loc')->values()->all();
    }

    public function xml(): string
    {
        $version = Cache::get(self::VERSION_KEY, 0);

        return Cache::remember("sitemap:xml:{$version}", now()->addDay(), fn (): string => view('platform::sitemap', ['entries' => $this->entries()])->render());
    }

    /** Content changed (FR-SEO-02: the sitemap follows the website). */
    public static function flush(): void
    {
        Cache::forever(self::VERSION_KEY, Cache::get(self::VERSION_KEY, 0) + 1);
    }

    /** @return iterable<Model> */
    protected function records(string $model): iterable
    {
        $query = $model::query()->withoutGlobalScope(UserScopeFilter::class);

        if (is_subclass_of($model, BaseModel::class)) {
            $query->frontendVisible();
        }

        if (in_array(HasSeo::class, class_uses_recursive($model), true)) {
            $query->whereDoesntHave('seoMeta', fn ($q) => $q->where('noindex', true));
        }

        return $query->lazyById(500);
    }

    /** @return array{loc: string, lastmod: ?string, alternates: array<string, string>} */
    protected function entry(string $path, ?string $lastmod, \Closure $pathIn): array
    {
        $alternates = [];
        $codes = $this->languages->activeCodes();

        if (count($codes) > 1) {
            foreach ($codes as $code) {
                $alternates[$code] = Seo::absolute(Seo::localized($pathIn($code), $code));
            }
        }

        return ['loc' => Seo::absolute($path), 'lastmod' => $lastmod, 'alternates' => $alternates];
    }
}
