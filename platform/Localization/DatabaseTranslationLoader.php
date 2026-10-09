<?php

namespace Platform\Localization;

use Illuminate\Contracts\Translation\Loader;
use Illuminate\Database\QueryException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;

/**
 * Laravel's file loader plus the admin's overrides from core_translations
 * (FR-LANG-03, TC-LANG-03). Files stay the defaults; a saved override wins.
 * Overrides are cached per group and language; any save starts a new cache generation.
 */
class DatabaseTranslationLoader implements Loader
{
    public const VERSION_KEY = 'platform:translations:version';

    public function __construct(protected Loader $files) {}

    public function load($locale, $group, $namespace = null): array
    {
        $lines = $this->files->load($locale, $group, $namespace);

        if ($group === '*') {
            return $lines;   // JSON strings: no overrides
        }

        foreach ($this->overrides($locale, $group, $namespace ?? '*') as $key => $text) {
            Arr::set($lines, $key, $text);
        }

        return $lines;
    }

    /** @return array<string, string> key => text */
    protected function overrides(string $locale, string $group, string $namespace): array
    {
        $version = Cache::get(self::VERSION_KEY, 0);

        try {
            return Cache::rememberForever("platform:translations:{$version}:{$namespace}:{$group}:{$locale}", fn (): array => Translation::query()
                ->where('namespace', $namespace)->where('group', $group)
                ->get()
                ->mapWithKeys(fn (Translation $t): array => [$t->key => $t->getTranslation('text', $locale, false)])
                ->filter(fn (?string $text): bool => $text !== null && $text !== '')
                ->all());
        } catch (QueryException) {
            return [];   // table not migrated yet
        }
    }

    /** The lang/ files alone, without overrides (the defaults the admin screen shows). */
    public function fileLoader(): Loader
    {
        return $this->files;
    }

    public static function flush(): void
    {
        Cache::forever(self::VERSION_KEY, Cache::get(self::VERSION_KEY, 0) + 1);
        app('translator')->setLoaded([]);
    }

    public function addNamespace($namespace, $hint): void
    {
        $this->files->addNamespace($namespace, $hint);
    }

    public function addJsonPath($path): void
    {
        $this->files->addJsonPath($path);
    }

    public function namespaces(): array
    {
        return $this->files->namespaces();
    }

    /** FileLoader extras (addPath, paths, jsonPaths). */
    public function __call(string $method, array $arguments): mixed
    {
        return $this->files->{$method}(...$arguments);
    }
}
