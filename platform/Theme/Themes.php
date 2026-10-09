<?php

namespace Platform\Theme;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use Illuminate\Support\HtmlString;
use Nwidart\Modules\Facades\Module;
use Platform\Backup\Backups;
use RuntimeException;

/**
 * Theme engine (C-06, SAD Part B §8): installed themes, the active one (or the
 * admin's preview), its views and translations, assets and options.
 *
 * View resolution (FR-THEME-10, §8.2):
 *   theme::…      → active theme views → kernel fallback (platform/resources/views/theme)
 *   faq::listing  → themes/{active}/views/modules/faq/listing → Modules/Faq/resources/views/listing
 */
class Themes
{
    public const ACTIVE_CACHE = 'theme:active';

    public const PREVIEW_SESSION = 'uwms.theme_preview';

    /** Who may preview (and activate) a theme (SRS C-06 themes.activate). */
    public const PREVIEW_PERMISSION = 'Activate:Theme';

    protected ?Theme $active = null;

    protected ?string $preview = null;

    protected ?string $applied = null;

    /** @var array<string, list<string>>|null module view namespace => its own paths */
    protected ?array $moduleHints = null;

    public function __construct(protected ThemeChecker $checker) {}

    /** View / Install / Activate / Customize:Theme; Super Admins may do everything. */
    public static function allows(?Authenticatable $user, string $permission): bool
    {
        return $user !== null && ((method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) || $user->can($permission));
    }

    public static function defaultSlug(): string
    {
        return config('platform.themes.default', 'default');
    }

    // ─── Installed themes ──────────────────────────────────────────────────────

    /** @return array<string, string> slug => folder, bundled first (themes/), then ZIP-installed */
    public function discover(): array
    {
        $found = [];

        foreach ([base_path('themes'), config('platform.themes.runtime_path')] as $root) {
            foreach (File::isDirectory($root) ? File::directories($root) : [] as $directory) {
                if (is_file("{$directory}/theme.json")) {
                    $slug = ThemeManifest::fromDirectory($directory)->slug();
                    $found[$slug] ??= $directory;
                }
            }
        }

        return $found;
    }

    /** theme_themes follows the folders; the default theme is active when nothing else is. */
    public function sync(): void
    {
        foreach ($this->discover() as $slug => $directory) {
            $manifest = ThemeManifest::fromDirectory($directory);
            $bundled = str_starts_with($directory, base_path('themes'));

            Theme::query()->updateOrCreate(['slug' => $slug], [
                'name' => $manifest->name(),
                'version' => $manifest->version(),
                'source_path' => $bundled ? ltrim(substr($directory, strlen(base_path())), '/') : $directory,
                'manifest' => $manifest->data,
                'is_bundled' => $bundled,
            ]);
        }

        if (! Theme::query()->where('is_active', true)->exists()) {
            Theme::query()->where('slug', self::defaultSlug())->update(['is_active' => true]);
        }

        $this->flush();
        $this->publishAssets($this->active());
    }

    /** @return Collection<int, Theme> */
    public function all(): Collection
    {
        return Theme::query()->orderByDesc('is_active')->orderBy('name')->get();
    }

    public function find(string $slug): ?Theme
    {
        return Theme::query()->where('slug', $slug)->first();
    }

    // ─── Active theme and preview ──────────────────────────────────────────────

    /** The theme the website shows (visitors never see a preview). */
    public function active(): Theme
    {
        if ($this->active) {
            return $this->active;
        }

        try {
            $slug = Cache::rememberForever(self::ACTIVE_CACHE, function (): ?string {
                if (! Theme::query()->exists()) {
                    $this->sync();
                }

                return Theme::query()->where('is_active', true)->value('slug');
            });

            $theme = $slug ? $this->find($slug) : null;
        } catch (QueryException) {
            $theme = null;   // not migrated yet: the bundled default from disk
        }

        if (! $theme || ! is_file($theme->path().'/theme.json')) {
            $theme = $this->fromDisk(self::defaultSlug());
        }

        return $this->active = $theme;
    }

    /** The theme this request renders: the admin's preview, or the active one. */
    public function current(): Theme
    {
        if ($this->preview && ($theme = $this->find($this->preview)) && is_file($theme->path().'/theme.json')) {
            return $theme;
        }

        return $this->active();
    }

    public function manifest(): ThemeManifest
    {
        return $this->current()->manifest();
    }

    /** FR-THEME-03: this request (and, via the session, this admin's next ones) uses another theme. */
    public function usePreview(?string $slug): void
    {
        $this->preview = $slug;
        $this->apply();
    }

    public function previewing(): ?string
    {
        return $this->preview && $this->current()->slug === $this->preview ? $this->preview : null;
    }

    /** Start previewing for the signed-in admin; null stops it. */
    public function startPreview(?string $slug): void
    {
        $slug === null
            ? session()->forget(self::PREVIEW_SESSION)
            : session()->put(self::PREVIEW_SESSION, $slug);

        $this->usePreview($slug);
    }

    /** @throws RuntimeException when the theme fails its checks */
    public function activate(string $slug): Theme
    {
        $theme = $this->find($slug) ?? throw new RuntimeException("Theme \"{$slug}\" is not installed.");

        if ($errors = $this->checker->check($theme->path())) {
            throw new RuntimeException(implode("\n", $errors));
        }

        $previous = $this->active();

        Theme::query()->where('is_active', true)->update(['is_active' => false]);
        $theme->forceFill(['is_active' => true])->save();

        $this->flush();
        $this->publishAssets($theme);

        activity('theme')->performedOn($theme)
            ->withProperties(['old' => ['theme' => $previous->slug], 'attributes' => ['theme' => $theme->slug]])
            ->event('activated')->log('activated');

        return $theme;
    }

    /**
     * FR-THEME-02: install a theme ZIP, or update an installed one (same slug, same or
     * newer version). Bundled themes are updated with the code, never by ZIP.
     *
     * @throws RuntimeException with every problem found
     */
    public function install(string $zipPath): Theme
    {
        $package = new ThemePackage($zipPath);

        if ($errors = $package->inspect()) {
            throw new RuntimeException(implode("\n", $errors));
        }

        $staged = $package->extractToStaging();

        try {
            if ($errors = $this->checker->check($staged)) {
                throw new RuntimeException(implode("\n", $errors));
            }

            $manifest = ThemeManifest::fromDirectory($staged);
            $existing = $this->find($manifest->slug());

            if ($existing?->is_bundled) {
                throw new RuntimeException("\"{$manifest->slug()}\" is a bundled theme; it is updated with the platform, not by ZIP.");
            }

            if ($existing && version_compare($manifest->version(), $existing->version, '<')) {
                throw new RuntimeException("Version {$manifest->version()} is older than the installed {$existing->version}.");
            }

            // FR-BKP-03: database backup before the theme changes
            $backup = app(Backups::class)->beforeChange("theme {$manifest->slug()} {$manifest->version()}");
            if ($backup['status'] === 'failed') {
                throw new RuntimeException('Database backup failed: '.$backup['message']);
            }

            $target = rtrim(config('platform.themes.runtime_path'), '/').'/'.$manifest->slug();
            File::deleteDirectory($target);
            File::ensureDirectoryExists(dirname($target));
            File::moveDirectory($staged, $target);
        } finally {
            File::deleteDirectory(dirname($staged));
        }

        $this->sync();
        $theme = $this->find($manifest->slug());

        if (! $existing) {
            $theme->forceFill(['installed_by' => auth()->id()])->save();
        } elseif ($theme->is_active) {
            $this->publishAssets($theme);   // new hashed files (Theme Guide §14)
        }

        activity('theme')->performedOn($theme)
            ->withProperties(['attributes' => ['theme' => $theme->slug, 'version' => $theme->version]])
            ->event($existing ? 'updated' : 'installed')->log($existing ? 'updated' : 'installed');

        return $theme;
    }

    /** The active theme and bundled themes can never be uninstalled (FR-THEME-02, BRL-THEME-01). */
    public function uninstall(string $slug): void
    {
        $theme = $this->find($slug) ?? throw new RuntimeException("Theme \"{$slug}\" is not installed.");

        if ($theme->is_active) {
            throw new RuntimeException('The active theme cannot be uninstalled; activate another one first.');
        }

        if ($theme->is_bundled) {
            throw new RuntimeException('A bundled theme cannot be uninstalled.');
        }

        if (str_starts_with($theme->path(), rtrim(config('platform.themes.runtime_path'), '/').'/')) {
            File::deleteDirectory($theme->path());
        }

        File::deleteDirectory(public_path("themes/{$slug}"));
        settings()->forgetGroup(DesignTokens::group($theme));

        activity('theme')->withProperties(['old' => ['theme' => $slug, 'version' => $theme->version]])
            ->event('uninstalled')->log('uninstalled');

        $theme->delete();
        $this->flush();
    }

    public function flush(): void
    {
        Cache::forget(self::ACTIVE_CACHE);
        $this->active = null;
        $this->applied = null;
        $this->moduleHints = null;
        app(DesignTokens::class)->forget();
    }

    // ─── Views, translations, assets ───────────────────────────────────────────

    /**
     * Points theme::, the module override folders and theme:: translations at the
     * theme this request renders. Cheap when nothing changed.
     */
    public function apply(): void
    {
        $theme = $this->current();
        $key = $theme->slug.'|'.$theme->path();

        if ($this->applied === $key) {
            return;
        }

        $finder = View::getFinder();
        $views = $theme->path().'/views';

        $finder->replaceNamespace('theme', array_values(array_filter([
            is_dir($views) ? $views : null,
            base_path('platform/resources/views/theme'),
        ])));

        // Module views: theme override first, then the module's own (FR-THEME-10)
        $this->moduleHints ??= $this->captureModuleHints($finder->getHints());

        foreach ($this->moduleHints as $namespace => $paths) {
            $override = "{$views}/modules/{$namespace}";
            $finder->replaceNamespace($namespace, is_dir($override) ? [$override, ...$paths] : $paths);
        }

        $finder->flush();
        app('translator')->addNamespace('theme', $theme->path().'/lang');

        $this->applied = $key;
    }

    /** Copies the theme's public/ to public/themes/{slug} (Theme Guide §12). */
    public function publishAssets(Theme $theme): void
    {
        $source = $theme->path().'/public';

        if (is_dir($source)) {
            File::copyDirectory($source, public_path("themes/{$theme->slug}"));
        }
    }

    /**
     * @themeVite(['resources/css/app.css', 'resources/js/app.js']): tags from the
     * theme's prebuilt manifest, or from its Vite dev server while "public/hot" exists.
     *
     * @param  string|list<string>  $entries
     */
    public function vite(string|array $entries): HtmlString
    {
        $theme = $this->current();
        $tags = [];

        if (is_file($hot = $theme->path().'/public/hot')) {
            $server = rtrim(trim((string) file_get_contents($hot)), '/');
            $tags[] = "<script type=\"module\" src=\"{$server}/@vite/client\"></script>";

            foreach ((array) $entries as $entry) {
                $tags[] = $this->tag("{$server}/{$entry}");
            }

            return new HtmlString(implode("\n", $tags));
        }

        $manifest = json_decode((string) @file_get_contents(public_path("themes/{$theme->slug}/build/manifest.json")), true) ?: [];

        foreach ((array) $entries as $entry) {
            if (isset($manifest[$entry]['file'])) {
                foreach ($manifest[$entry]['css'] ?? [] as $css) {
                    $tags[] = $this->tag(asset("themes/{$theme->slug}/build/{$css}"));
                }

                $tags[] = $this->tag(asset("themes/{$theme->slug}/build/{$manifest[$entry]['file']}"));
            }
        }

        return new HtmlString(implode("\n", array_unique($tags)));
    }

    // ─── What the theme offers ─────────────────────────────────────────────────

    /** Theme option (Theme Guide §11): saved value, else the theme.json default. */
    public function option(string $key, mixed $default = null): mixed
    {
        $theme = $this->current();
        $saved = (array) (settings()->group(DesignTokens::group($theme))['options'] ?? []);

        if (array_key_exists($key, $saved)) {
            return $saved[$key];
        }

        foreach ($theme->manifest()->options() as $option) {
            if (($option['key'] ?? null) === $key) {
                return $option['default'] ?? $default;
            }
        }

        return $default;
    }

    /** @param array<string, mixed> $values */
    public function saveOptions(Theme $theme, array $values): void
    {
        $keys = collect($theme->manifest()->options())->pluck('key')->all();
        settings()->save(DesignTokens::group($theme), ['options' => array_intersect_key($values, array_flip($keys)) ?: null]);
    }

    /** @return list<string> */
    public function variants(string $region): array
    {
        return $this->active()->manifest()->variants($region);
    }

    /** @return array<string, string> location => label: the platform's standard ones plus the theme's own */
    public function menuLocations(): array
    {
        return $this->active()->manifest()->menuLocations();
    }

    protected function fromDisk(string $slug): Theme
    {
        $directory = base_path("themes/{$slug}");
        $manifest = is_file("{$directory}/theme.json") ? ThemeManifest::fromDirectory($directory)->data : ['slug' => $slug, 'name' => $slug, 'version' => '0.0.0'];

        return (new Theme)->forceFill([
            'slug' => $slug, 'name' => $manifest['name'] ?? $slug, 'version' => $manifest['version'] ?? '0.0.0',
            'source_path' => "themes/{$slug}", 'manifest' => $manifest, 'is_active' => true, 'is_bundled' => true,
        ]);
    }

    /**
     * @param  array<string, list<string>>  $hints
     * @return array<string, list<string>>
     */
    protected function captureModuleHints(array $hints): array
    {
        $namespaces = collect(Module::allEnabled())->map(fn ($module): string => $module->getLowerName())->all();

        // Without any theme override path added by an earlier apply()
        return array_map(
            fn (array $paths): array => array_values(array_filter($paths, fn (string $p): bool => ! preg_match('#/views/modules/[^/]+$#', str_replace('\\', '/', $p)))),
            array_intersect_key($hints, array_flip($namespaces)),
        );
    }

    protected function tag(string $url): string
    {
        return str_ends_with(parse_url($url, PHP_URL_PATH) ?? '', '.css')
            ? "<link rel=\"stylesheet\" href=\"{$url}\">"
            : "<script type=\"module\" src=\"{$url}\"></script>";
    }
}
