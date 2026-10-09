<?php

namespace Modules\Settings\Providers;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\Facades\Event;
use Modules\Settings\Filament\Pages\ManageSettings;
use Modules\Settings\Http\Middleware\SiteMaintenance;
use Modules\Settings\Support\MailConfig;
use Platform\Events\SettingsUpdated;
use Platform\Modules\ModuleServiceProvider;

class SettingsServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Settings';

    protected string $nameLower = 'settings';

    public function register(): void
    {
        parent::register();

        // Guarded by the custom permissions View / Update / Advanced:Settings, not a page permission
        config()->push('filament-shield.pages.exclude', ManageSettings::class);
    }

    public function boot(): void
    {
        parent::boot();

        // On the HTTP kernel: it re-syncs its groups to the router on every request
        $this->app->make(Kernel::class)->appendMiddlewareToGroup('web', SiteMaintenance::class);

        MailConfig::apply();
        Event::listen(fn (SettingsUpdated $event) => $event->group === 'mail' ? MailConfig::apply() : null);
    }
}
