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
    /** theme_asset('images/footer-cta.jpg') — a file in the shown theme's public/ folder (Theme Guide §12). */
    function theme_asset(string $path): string
    {
        return asset('themes/'.app(Themes::class)->current()->slug.'/'.ltrim($path, '/'));
    }
}

if (! function_exists('theme_image')) {
    /**
     * theme_image('footer_cta_image', 'images/footer-cta.jpg') — the picture an "image" theme
     * option points to (Media Library), else the theme's own default file, else null.
     */
    function theme_image(string $option, ?string $default = null): ?string
    {
        $id = theme_option($option);
        $media = $id ? MediaItem::query()->where('is_active', true)->find(is_array($id) ? reset($id) : $id) : null;

        return $media?->url() ?? ($default !== null ? theme_asset($default) : null);
    }
}

if (! function_exists('module_asset')) {
    /** module_asset('documents', 'pdfjs/pdf.min.mjs') — a module's own public file (SAD Part A §9), versioned. */
    function module_asset(string $slug, string $path): string
    {
        return app(ModuleAssets::class)->url($slug, $path);
    }
}
