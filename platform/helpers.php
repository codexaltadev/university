<?php

use Platform\Media\MediaItem;
use Platform\Modules\ModuleAssets;
use Platform\Settings\Settings;
use Platform\Theme\Themes;

if (! function_exists('settings')) {
    /**
     * settings('contact.phone') — a website setting (C-05), in the current language.
     * settings() — the Settings service.
     */
    function settings(?string $path = null, mixed $default = null): mixed
    {
        $settings = app(Settings::class);

        return $path === null ? $settings : $settings->get($path, $default);
    }
}

if (! function_exists('theme_option')) {
    /** theme_option('sticky_header') — an option of the theme being shown (Theme Guide §11). */
    function theme_option(string $key, mixed $default = null): mixed
    {
        return app(Themes::class)->option($key, $default);
    }
}

if (! function_exists('theme_asset')) {
    /** theme_asset('images/footer-cta.webp') — a file in the shown theme's public/ folder (Theme Guide §12). */
    function theme_asset(string $path): string
    {
        return asset('themes/'.app(Themes::class)->current()->slug.'/'.ltrim($path, '/'));
    }
}

if (! function_exists('theme_image')) {
    /**
     * theme_image('footer_cta_image', 'images/footer-cta.webp') — the picture an "image" theme
     * option points to (Media Library), else the theme's own default file, else null.
     */
    function theme_image(string $option, ?string $default = null): ?string
    {
        $id = theme_option($option);
        $media = $id ? MediaItem::query()->where('is_active', true)->find(is_array($id) ? reset($id) : $id) : null;

        return $media?->url() ?? ($default !== null ? theme_asset($default) : null);
    }
}

if (! function_exists('theme_image_small')) {
    /**
     * theme_image_small($image) — the phone copy ("-sm" beside it) of a theme's own picture, for a <picture>
     * source on small screens (Phase 4.8c); null for any other address or when the theme has no such copy.
     */
    function theme_image_small(?string $url): ?string
    {
        $base = rtrim(theme_asset(''), '/').'/';
        if ($url === null || ! str_starts_with($url, $base)) {
            return null;
        }

        $small = preg_replace('/\.(webp|jpe?g|png)$/i', '-sm.webp', substr($url, strlen($base)));
        $theme = app(Themes::class)->current();

        return $small !== null && is_file($theme->path().'/public/'.$small) ? theme_asset($small) : null;
    }
}

if (! function_exists('media_url')) {
    /** media_url($id, 'large') — address of a Media Library item (a MediaPicker value: id or list of ids), or null. */
    function media_url(int|string|array|null $id, ?string $conversion = null): ?string
    {
        $id = is_array($id) ? reset($id) : $id;
        $media = $id ? MediaItem::query()->where('is_active', true)->find($id) : null;

        return $media?->url($conversion);
    }
}

if (! function_exists('link_list')) {
    /**
     * link_list($faculty->quick_links) — a LinkListField value ([{label: {en, bn}, url}]) as
     * [['label' => in the current language, 'url' => full address]]; rows without a label or address are left out.
     *
     * @return list<array{label: string, url: string}>
     */
    function link_list(?array $items): array
    {
        $out = [];
        foreach ($items ?? [] as $item) {
            $label = app(Settings::class)->translate($item['label'] ?? null);
            $url = trim((string) ($item['url'] ?? ''));
            if (filled($label) && $url !== '') {
                $out[] = ['label' => (string) $label, 'url' => preg_match('#^(https?:|mailto:|tel:|\#)#i', $url) ? $url : url($url)];
            }
        }

        return $out;
    }
}

if (! function_exists('module_asset')) {
    /** module_asset('documents', 'pdfjs/pdf.min.mjs') — a module's own public file (SAD Part A §9), versioned. */
    function module_asset(string $slug, string $path): string
    {
        return app(ModuleAssets::class)->url($slug, $path);
    }
}
