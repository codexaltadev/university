<?php

namespace Platform\Modules;

use Nwidart\Modules\Support\ModuleServiceProvider as NwidartModuleServiceProvider;

/**
 * Base service provider of every module. Same as nwidart's, but translations are
 * namespaced by the module ("faq::faq.title"), as the Module Guide requires;
 * nwidart loads a module's lang folder without a namespace.
 */
abstract class ModuleServiceProvider extends NwidartModuleServiceProvider
{
    public function register(): void
    {
        parent::register();

        // The role editor's "Custom permissions" tab lists the active modules' manifest custom_permissions
        $manifest = json_decode((string) @file_get_contents(module_path($this->name, 'module.json')), true);

        foreach ((array) ($manifest['uwms']['custom_permissions'] ?? []) as $permission) {
            config()->push('filament-shield.custom_permissions', $permission);
        }
    }

    protected function registerTranslations(): void
    {
        $override = resource_path('lang/modules/'.$this->nameLower);
        $path = is_dir($override) ? $override : module_path($this->name, 'lang');

        $this->loadTranslationsFrom($path, $this->nameLower);
        $this->loadJsonTranslationsFrom($path);
    }
}
