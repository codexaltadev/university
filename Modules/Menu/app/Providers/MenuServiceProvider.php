<?php

namespace Modules\Menu\Providers;

use Illuminate\Support\Facades\Event;
use Modules\Menu\Filament\Pages\ManageMenus;
use Modules\Menu\Models\Menu;
use Modules\Menu\Support\MenuEditor;
use Modules\Menu\Support\MenuLinks;
use Modules\Menu\Support\MenuTree;
use Platform\Context\FallbackResolver;
use Platform\Models\BaseModel;
use Platform\Modules\ModuleServiceProvider;
use Platform\Scope\UserScopeFilter;

class MenuServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Menu';

    protected string $nameLower = 'menu';

    public function register(): void
    {
        parent::register();

        foreach ([MenuLinks::class, MenuTree::class, MenuEditor::class] as $service) {
            $this->app->singleton($service);
        }

        // Guarded by Manage:Menu / ManageMain:Menu, not a page permission
        config()->push('filament-shield.pages.exclude', ManageMenus::class);
    }

    public function boot(): void
    {
        parent::boot();

        // BRL-MENU-01: own → parent … → main site, per location (SAD Part B §6.2)
        $this->app->make(FallbackResolver::class)->store('menu', fn (string $type, int $id, ?string $location): ?Menu => Menu::query()
            ->withoutGlobalScope(UserScopeFilter::class)
            ->where('owner_type', $type)->where('owner_id', $id)->where('location', $location)
            ->where('is_active', true)->first());

        // FR-MENU-12, AC-MENU-03: a menu or a linked page / record changed → fresh menus
        Event::listen(['eloquent.saved: *', 'eloquent.deleted: *', 'eloquent.restored: *'], function (string $event, array $models): void {
            if (($models[0] ?? null) instanceof BaseModel) {
                MenuTree::flush();
            }
        });
    }
}
