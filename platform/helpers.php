<?php

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

if (! function_exists('module_asset')) {
    /** module_asset('documents', 'pdfjs/pdf.min.mjs') — a module's own public file (SAD Part A §9), versioned. */
    function module_asset(string $slug, string $path): string
    {
        return app(ModuleAssets::class)->url($slug, $path);
    }
}
