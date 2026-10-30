<?php

namespace Platform\Theme;

use Composer\Semver\Semver;
use Platform\Homepage\ThemeSections;
use RuntimeException;
use Throwable;

/**
 * theme.json (Theme Guide §4, SAD Part B §8.1): what a theme offers — layouts,
 * menu locations, header / footer variants, page and microsite templates, token
 * defaults and its own options.
 */
final class ThemeManifest
{
    /** FR-PAGE-04: every theme has these page templates. */
    public const PAGE_TEMPLATES = ['default', 'full-width', 'with-sidebar', 'landing'];

    /** Layout views every theme must have (T-3). */
    public const REQUIRED_VIEWS = ['layouts/app.blade.php', 'layouts/microsite.blade.php'];

    public const OPTION_TYPES = ['toggle', 'select', 'text', 'number', 'color', 'image'];

    /** @param array<string, mixed> $data */
    public function __construct(public readonly array $data, public readonly string $path) {}

    public static function fromDirectory(string $directory): self
    {
        $file = rtrim($directory, '/').'/theme.json';

        if (! is_file($file)) {
            throw new RuntimeException("theme.json not found in {$directory}");
        }

        $data = json_decode((string) file_get_contents($file), true);

        if (! is_array($data)) {
            throw new RuntimeException("theme.json in {$directory} is not valid JSON");
        }

        return new self($data, rtrim($directory, '/'));
    }

    public function slug(): string
    {
        return (string) ($this->data['slug'] ?? '');
    }

    public function name(): string
    {
        return (string) ($this->data['name'] ?? $this->slug());
    }

    public function version(): string
    {
        return (string) ($this->data['version'] ?? '0.0.0');
    }

    public function description(): ?string
    {
        return $this->data['description'] ?? null;
    }

    /** @return array<string, list<string>> page ("faq.listing") => layouts this theme draws */
    public function layouts(): array
    {
        return (array) ($this->data['layouts'] ?? []);
    }

    /** @return array<string, string> location key => label */
    public function menuLocations(): array
    {
        return (array) ($this->data['menu_locations'] ?? []);
    }

    /** @return list<string> variants of "topbar", "header" or "footer" */
    public function variants(string $region): array
    {
        return array_values((array) ($this->data["{$region}_variants"] ?? []));
    }

    /** @return list<string> */
    public function pageTemplates(): array
    {
        return array_values(array_unique([...self::PAGE_TEMPLATES, ...(array) ($this->data['page_templates'] ?? [])]));
    }

    /** @return list<string> */
    public function micrositeTemplates(): array
    {
        return array_values((array) ($this->data['microsite_templates'] ?? ['standard'])) ?: ['standard'];
    }

    /** @return array<string, string> token => default value (without "--") */
    public function tokens(): array
    {
        return (array) ($this->data['tokens'] ?? []);
    }

    /** @return list<string> font families the theme carries in its own CSS (@font-face): never asked of Google Fonts */
    public function fonts(): array
    {
        return array_values((array) ($this->data['fonts'] ?? []));
    }

    /** @return list<array{key: string, type: string, label?: string, options?: list<string>, default?: mixed}> */
    public function options(): array
    {
        return array_values((array) ($this->data['options'] ?? []));
    }

    /** @return array<string, array<string, mixed>> name => {label, source?, fields} — homepage sections of the theme (Theme Guide §8.4) */
    public function sections(): array
    {
        return (array) ($this->data['sections'] ?? []);
    }

    public function screenshot(): ?string
    {
        $file = $this->path.'/'.($this->data['screenshot'] ?? 'screenshot.png');

        return is_file($file) ? $file : null;
    }

    /**
     * Manifest and folder checks (FR-THEME-02, Theme Guide §2 T-3/T-6).
     *
     * @return list<string> errors
     */
    public function validate(): array
    {
        $errors = [];

        foreach (['slug', 'name', 'version', 'requires_core'] as $field) {
            if (blank($this->data[$field] ?? null)) {
                $errors[] = "theme.json: \"{$field}\" is required.";
            }
        }

        if (filled($this->slug()) && ! preg_match('/^[a-z][a-z0-9-]{1,49}$/', $this->slug())) {
            $errors[] = 'theme.json: "slug" must be lowercase letters, digits and dashes.';
        }

        if (filled($this->data['requires_core'] ?? null)) {
            try {
                if (! Semver::satisfies(config('platform.version'), (string) $this->data['requires_core'])) {
                    $errors[] = "Theme needs core {$this->data['requires_core']}; this site runs ".config('platform.version').'.';
                }
            } catch (Throwable) {
                $errors[] = 'theme.json: "requires_core" is not a valid version constraint.';
            }
        }

        foreach (self::REQUIRED_VIEWS as $view) {
            if (! is_file("{$this->path}/views/{$view}")) {
                $errors[] = "Missing required view: views/{$view}";
            }
        }

        foreach (self::PAGE_TEMPLATES as $template) {
            if (! is_file("{$this->path}/views/templates/{$template}.blade.php")) {
                $errors[] = "Missing page template: views/templates/{$template}.blade.php";
            }
        }

        foreach (['header', 'footer'] as $region) {
            if ($this->variants($region) === []) {
                $errors[] = "theme.json: at least one {$region} variant is required.";
            }
        }

        foreach ($this->options() as $option) {
            if (blank($option['key'] ?? null) || ! in_array($option['type'] ?? null, self::OPTION_TYPES, true)) {
                $errors[] = 'theme.json: every option needs a "key" and a "type" ('.implode(', ', self::OPTION_TYPES).').';
            }
        }

        $errors = [...$errors, ...ThemeSections::validate($this->sections(), $this->path)];

        if (! is_file("{$this->path}/public/build/manifest.json")) {
            $errors[] = 'Missing compiled assets: public/build/manifest.json (T-6).';
        }

        return $errors;
    }
}
