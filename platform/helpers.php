<?php

use Platform\Settings\Settings;

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
