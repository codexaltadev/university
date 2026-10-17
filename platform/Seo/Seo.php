<?php

namespace Platform\Seo;

use Illuminate\Database\Eloquent\Model;
use Platform\Concerns\HasSeo;
use Platform\Localization\Languages;
use Platform\Theme\Themes;

/**
 * SEO of the page being rendered (C-12, FR-SEO-01/08, FR-GEN-70/71). The controller
 * tells it the record (or plain values); <x-platform::head /> prints tags():
 * title by the pattern "{title} | {site_name}", description, canonical, robots,
 * Open Graph, hreflang, verification codes and JSON-LD.
 *
 *   app(Seo::class)->forRecord($page);
 *   app(Seo::class)->set(['title' => 'News', 'type' => 'website']);
 */
class Seo
{
    public const DEFAULT_PATTERN = '{title} | {site_name}';

    protected ?Model $record = null;

    /** @var array<string, mixed> */
    protected array $values = [];

    public function forRecord(Model $record): static
    {
        $this->record = $record;

        return $this;
    }

    /** @param array<string, mixed> $values title, description, canonical, noindex, type, alternates, jsonld, home */
    public function set(array $values): static
    {
        $this->values = [...$this->values, ...$values];

        return $this;
    }

    public function record(): ?Model
    {
        return $this->record;
    }

    public function reset(): void
    {
        $this->record = null;
        $this->values = [];
    }

    /** @return array<string, mixed> everything the head prints */
    public function tags(?string $title = null, ?string $description = null): array
    {
        $locale = app()->getLocale();
        $record = $this->record && in_array(HasSeo::class, class_uses_recursive($this->record), true) ? $this->record->seo($locale) : [];
        $siteName = (string) settings('general.site_name');

        $title = $record['title'] ?? $this->values['title'] ?? $title;
        $image = $record['image'] ?? null;
        $imageUrl = $image?->url() ?? settings()->media('seo.og_image')?->url() ?? settings()->media('branding.logo_light')?->url();
        $noindex = ($record['noindex'] ?? false) || ($this->values['noindex'] ?? false) || app(Themes::class)->previewing() !== null;

        return [
            'title' => self::title($title, $siteName),
            'og_title' => $title ?: $siteName,
            'description' => $record['description'] ?? $this->values['description'] ?? $description
                ?? settings('seo.default_description') ?? settings('general.tagline'),
            'canonical' => self::absolute($record['canonical'] ?? $this->values['canonical'] ?? request()->url()),
            'robots' => $noindex ? 'noindex, nofollow' : null,
            'type' => $this->values['type'] ?? ($this->record ? 'article' : 'website'),
            'image' => $imageUrl ? self::absolute($imageUrl) : null,
            'site_name' => $siteName,
            'locale' => $locale,
            'alternates' => $this->alternates(),
            'google' => settings('seo.google_verification'),
            'bing' => settings('seo.bing_verification'),
            'jsonld' => $this->jsonLd($record['jsonld'] ?? []),
        ];
    }

    /** FR-SEO-01: the pattern with {title} and {site_name}; no title = the site name alone. */
    public static function title(?string $title, string $siteName): string
    {
        if (blank($title)) {
            return $siteName;
        }

        $pattern = (string) (settings('seo.title_pattern') ?: self::DEFAULT_PATTERN);

        return trim(strtr($pattern, ['{title}' => $title, '{site_name}' => $siteName]));
    }

    /**
     * An address in a language (FR-GEN-33): the default language without a prefix,
     * others with /{locale}.
     */
    public static function localized(string $path, string $locale): string
    {
        $path = '/'.ltrim($path, '/');

        return $locale === app(Languages::class)->default() ? $path : '/'.$locale.($path === '/' ? '' : $path);
    }

    public static function absolute(string $url): string
    {
        return preg_match('#^https?://#i', $url) ? $url : url($url);
    }

    /** @return array<string, string> hreflang => URL (and x-default) */
    protected function alternates(): array
    {
        $languages = app(Languages::class);

        if (count($languages->activeCodes()) < 2) {
            return [];
        }

        $alternates = [];

        foreach ($languages->activeCodes() as $code) {
            $path = $this->record && method_exists($this->record, 'url')
                ? $this->record->url($code)
                : ($this->values['path'] ?? '/'.ltrim(request()->path(), '/'));

            $alternates[$code] = self::absolute(self::localized($this->stripLocale($path), $code));
        }

        $alternates['x-default'] = $alternates[$languages->default()] ?? reset($alternates);

        return $alternates;
    }

    /**
     * The language switcher's links (UI/UX Part B §6): this page in every website language;
     * empty while the website has one language.
     *
     * @return list<array{code: string, label: string, url: string, current: bool}>
     */
    public function languageLinks(): array
    {
        $labels = app(Languages::class)->labels();

        return collect($this->alternates())
            ->except('x-default')
            ->map(fn (string $url, string $code): array => [
                'code' => $code,
                'label' => (string) ($labels[$code] ?? strtoupper($code)),
                'url' => $url,
                'current' => $code === app()->getLocale(),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $recordLd
     * @return list<array<string, mixed>>
     */
    protected function jsonLd(array $recordLd): array
    {
        $blocks = [];

        if ($this->values['home'] ?? false) {
            $organization = array_filter([
                '@type' => settings('seo.organization_type') ?: 'CollegeOrUniversity',
                'name' => settings('general.university_name') ?: settings('general.site_name'),
                'url' => url('/'),
                'logo' => settings()->media('branding.logo_light')?->url(),
                'telephone' => settings('contact.phone'),
                'email' => settings('contact.email'),
                'sameAs' => array_values(array_filter(array_column((array) settings('social.links', []), 'url'))) ?: null,
            ]);
            $blocks[] = ['@context' => 'https://schema.org', ...$organization];
            $blocks[] = ['@context' => 'https://schema.org', '@type' => 'WebSite', 'name' => settings('general.site_name'), 'url' => url('/')];
        }

        if ($recordLd !== []) {
            $blocks[] = ['@context' => 'https://schema.org', ...$recordLd];
        }

        foreach ((array) ($this->values['jsonld'] ?? []) as $block) {
            $blocks[] = ['@context' => 'https://schema.org', ...$block];
        }

        return $blocks;
    }

    protected function stripLocale(string $path): string
    {
        $segments = explode('/', ltrim($path, '/'));

        return in_array($segments[0] ?? '', app(Languages::class)->codes(), true)
            ? '/'.implode('/', array_slice($segments, 1))
            : '/'.ltrim($path, '/');
    }
}
