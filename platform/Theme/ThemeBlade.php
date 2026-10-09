<?php

namespace Platform\Theme;

use Illuminate\Support\Facades\Blade;
use Platform\Modules\ModuleRegistry;

/**
 * Blade helpers themes use (Theme Guide §5):
 *   <x-platform::head />, <x-platform::slot name="header" />, <x-platform::menu location="primary" />,
 *   <x-platform::logo />, <x-platform::homepage :context="$context" />,
 *   <x-theme::layout>, the directives themeVite, moduleActive and themeOption,
 *   and the theme_option('key') helper.
 */
final class ThemeBlade
{
    public static function register(): void
    {
        Blade::anonymousComponentNamespace('platform::components', 'platform');
        Blade::anonymousComponentNamespace('theme::components', 'theme');

        // T-4: a module-dependent part shows only while the module is on and not hidden (FR-SET-05)
        Blade::if('moduleActive', fn (string $slug): bool => app(ModuleRegistry::class)->isActive($slug)
            && ! settings()->moduleHiddenOnWebsite($slug));

        Blade::if('themeOption', fn (string $key): bool => (bool) theme_option($key));

        Blade::directive('themeVite', fn (string $expression): string => "<?php echo app(\\Platform\\Theme\\Themes::class)->vite({$expression}); ?>");
    }
}
