<?php

namespace Modules\Localization\Providers;

use Illuminate\Support\Facades\Gate;
use Modules\Localization\Filament\Pages\InterfaceTexts;
use Modules\Localization\Filament\Resources\Languages\LanguageResource;
use Modules\Localization\Policies\LanguagePolicy;
use Platform\Localization\Language;
use Platform\Modules\ModuleServiceProvider;

class LocalizationServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Localization';

    protected string $nameLower = 'localization';

    public function register(): void
    {
        parent::register();

        // System table: no trash, so no restore / force delete permissions
        config(['filament-shield.resources.manage.'.LanguageResource::class => ['viewAny', 'view', 'create', 'update', 'delete', 'reorder']]);

        // Guarded by the custom permission Update:InterfaceText, not a page permission
        config()->push('filament-shield.pages.exclude', InterfaceTexts::class);
    }

    public function boot(): void
    {
        parent::boot();

        Gate::policy(Language::class, LanguagePolicy::class);
    }
}
