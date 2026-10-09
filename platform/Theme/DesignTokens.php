<?php

namespace Platform\Theme;

use Illuminate\Support\Facades\Storage;
use Platform\Settings\Settings;

/**
 * Design tokens → CSS variables (FR-THEME-04/05/07, SAD Part B §8.4, UI/UX Part B §2).
 *
 *   kernel defaults ← theme.json "tokens" ← admin's Design settings (core_settings "theme.{slug}")
 *
 * The CSS is written once per content hash to the public disk
 * (theme/{slug}-{hash}.css); a change gives a new file and the old one is removed.
 */
class DesignTokens
{
    public const COLORS = [
        'color-primary', 'color-secondary', 'color-accent', 'color-bg', 'color-surface',
        'color-text', 'color-text-muted', 'color-border', 'color-success', 'color-warning', 'color-danger',
    ];

    public const FONTS = ['font-heading', 'font-body', 'font-bn'];

    /** Platform defaults (UI/UX Part B §2); a theme overrides them in theme.json. */
    public const DEFAULTS = [
        'color-primary' => '#034EA2',
        'color-secondary' => '#002652',
        'color-accent' => '#39B54A',
        'color-bg' => '#FFFFFF',
        'color-surface' => '#F3F4F9',
        'color-text' => '#1F2328',
        'color-text-muted' => '#5B6470',
        'color-border' => '#E3E6EA',
        'color-success' => '#1E7B34',
        'color-warning' => '#9A5B00',
        'color-danger' => '#C62828',
        'font-heading' => 'Montserrat',
        'font-body' => 'Manrope',
        'font-bn' => 'Hind Siliguri',
        'font-size-base' => '16px',
        'line-height-body' => '1.6',
        'line-height-body-bn' => '1.8',
        'radius-sm' => '4px',
        'radius-md' => '8px',
        'radius-lg' => '16px',
        'container-max' => '1280px',
        'shadow-card' => '0 1px 3px rgba(16, 24, 40, .08), 0 1px 2px rgba(16, 24, 40, .04)',
    ];

    /** Fonts the Design screen offers, loaded from Google Fonts (FR-THEME-05). */
    public const GOOGLE_FONTS = [
        'Montserrat', 'Manrope', 'Poppins', 'Inter', 'Roboto', 'Open Sans', 'Lato', 'Nunito', 'Source Sans 3',
        'Merriweather', 'Playfair Display', 'Hind Siliguri', 'Noto Sans Bengali', 'Noto Serif Bengali', 'Baloo Da 2',
        'Anek Bangla', 'Tiro Bangla',
    ];

    /** --text-xs … --text-4xl in px (UI/UX Part B §2.2) */
    public const TYPE_SCALE = ['xs' => 14, 'sm' => 16, 'base' => 18, 'lg' => 20, 'xl' => 24, '2xl' => 30, '3xl' => 36, '4xl' => 48];

    /** @var array<string, string> theme slug => stylesheet URL, per request */
    protected array $urls = [];

    public function __construct(protected Settings $settings) {}

    public static function group(Theme $theme): string
    {
        return "theme.{$theme->slug}";
    }

    /** @return array<string, string> the theme's own defaults over the kernel's */
    public function defaults(Theme $theme): array
    {
        return array_merge(self::DEFAULTS, array_intersect_key($theme->manifest()->tokens(), self::DEFAULTS));
    }

    /** @return array<string, string> what the admin changed (only keys that differ from the theme default) */
    public function overrides(Theme $theme): array
    {
        return array_intersect_key((array) ($this->settings->group(self::group($theme))['tokens'] ?? []), self::DEFAULTS);
    }

    /** @return array<string, string> */
    public function values(Theme $theme): array
    {
        return array_merge($this->defaults($theme), array_filter($this->overrides($theme), fn ($v): bool => filled($v)));
    }

    /**
     * Keep only real changes; nothing changed = "Reset to theme default" (FR-THEME-12).
     *
     * @param  array<string, mixed>  $tokens
     */
    public function save(Theme $theme, array $tokens): void
    {
        $defaults = $this->defaults($theme);
        $changed = collect($tokens)
            ->only(array_keys(self::DEFAULTS))
            ->filter(fn ($value, string $key): bool => filled($value) && $value !== $defaults[$key])
            ->map(fn ($value): string => (string) $value)
            ->all();

        $this->settings->save(self::group($theme), ['tokens' => $changed ?: null]);
        unset($this->urls[$theme->slug]);
    }

    public function reset(Theme $theme): void
    {
        $this->save($theme, []);
    }

    /**
     * WCAG warnings for the colour choices (FR-THEME-04): text-like colours on the background.
     *
     * @param  array<string, string>  $values
     * @return array<string, float> token => contrast ratio, for pairs below AA
     */
    public static function contrastWarnings(array $values): array
    {
        $bg = $values['color-bg'] ?? '#FFFFFF';
        $warnings = [];

        foreach (['color-text', 'color-text-muted', 'color-primary', 'color-secondary', 'color-danger'] as $token) {
            if (Contrast::isHex($values[$token] ?? null) && Contrast::isHex($bg) && ($ratio = Contrast::ratio($values[$token], $bg)) < Contrast::AA) {
                $warnings[$token] = $ratio;
            }
        }

        return $warnings;
    }

    /** The :root block and the Bangla font rule. */
    public function css(Theme $theme): string
    {
        $v = $this->values($theme);
        $vars = [];

        foreach ($v as $key => $value) {
            $vars[$key] = in_array($key, self::FONTS, true) ? self::fontStack($value) : self::clean($value);
        }

        // Text on coloured backgrounds: automatic white / dark (UI/UX Part B §2.1)
        $vars['color-primary-contrast'] = Contrast::isHex($v['color-primary']) ? Contrast::readableOn($v['color-primary']) : '#FFFFFF';
        $vars['color-secondary-contrast'] = Contrast::isHex($v['color-secondary']) ? Contrast::readableOn($v['color-secondary']) : '#FFFFFF';
        $vars['color-accent-contrast'] = Contrast::isHex($v['color-accent']) && Contrast::isHex($v['color-secondary'])
            ? Contrast::readableOn($v['color-accent'], $v['color-secondary']) : '#111827';
        $vars['gradient-primary'] = 'linear-gradient(90deg, var(--color-primary), var(--color-secondary))';

        foreach ([1 => 4, 2 => 8, 3 => 12, 4 => 16, 5 => 20, 6 => 24, 8 => 32, 10 => 40, 12 => 48, 16 => 64] as $step => $px) {
            $vars["space-{$step}"] = "{$px}px";
        }

        foreach (self::TYPE_SCALE as $name => $px) {
            $vars["text-{$name}"] = round($px / 16, 4).'rem';
        }

        $css = ":root {\n";
        foreach ($vars as $key => $value) {
            $css .= "  --{$key}: {$value};\n";
        }
        $css .= "}\n";

        // Bangla text: its own font and a taller line (UI/UX Part B §2.2)
        return $css.":lang(bn) {\n  --font-heading: var(--font-bn);\n  --font-body: var(--font-bn);\n  --line-height-body: var(--line-height-body-bn);\n}\n";
    }

    /** Stylesheet URL; the file is written the first time its hash is seen. */
    public function stylesheetUrl(Theme $theme): string
    {
        if (isset($this->urls[$theme->slug])) {
            return $this->urls[$theme->slug];
        }

        $css = $this->css($theme);
        $file = "theme/{$theme->slug}-".substr(md5($css), 0, 12).'.css';
        $disk = Storage::disk('public');

        if (! $disk->exists($file)) {
            foreach ($disk->files('theme') as $old) {
                if (str_starts_with(basename($old), "{$theme->slug}-")) {
                    $disk->delete($old);
                }
            }

            $disk->put($file, $css);
        }

        return $this->urls[$theme->slug] = $disk->url($file);
    }

    /** Google Fonts stylesheet for the chosen families, or null (uploaded / system fonts only). */
    public function fontsUrl(Theme $theme): ?string
    {
        $values = $this->values($theme);
        $families = collect(self::FONTS)
            ->map(fn (string $token): string => $values[$token])
            ->filter(fn (string $font): bool => in_array($font, self::GOOGLE_FONTS, true))
            ->unique()
            ->map(fn (string $font): string => 'family='.str_replace(' ', '+', $font).':wght@400;500;600;700')
            ->implode('&');

        return $families === '' ? null : "https://fonts.googleapis.com/css2?{$families}&display=swap";
    }

    public function forget(): void
    {
        $this->urls = [];
    }

    protected static function fontStack(string $font): string
    {
        $font = trim(str_replace(['"', "'", ';', '{', '}', '<', '>'], '', $font));

        return "\"{$font}\", system-ui, -apple-system, \"Segoe UI\", sans-serif";
    }

    /** A stored value can never end the :root block. */
    protected static function clean(string $value): string
    {
        return trim(str_replace([';', '{', '}', '<', '>'], '', $value));
    }
}
