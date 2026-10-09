<?php

namespace Modules\ModuleManager\Filament\Resources\Modules\Pages;

use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Modules\ModuleManager\Filament\Resources\Modules\ModuleActions;
use Modules\ModuleManager\Filament\Resources\Modules\ModuleResource;
use Platform\Models\CoreModule;
use Platform\Modules\ModuleManager;

class ListModules extends ListRecords
{
    protected static string $resource = ModuleResource::class;

    /** Bundled modules appear even before anyone recorded them. */
    public function mount(): void
    {
        app(ModuleManager::class)->syncBundled();

        parent::mount();
    }

    protected function getHeaderActions(): array
    {
        return [
            ModuleActions::install(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make(__('modulemanager::manager.tabs.all')),
            'active' => Tab::make(__('modulemanager::manager.tabs.active'))
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', CoreModule::STATUS_ACTIVE)),
            'inactive' => Tab::make(__('modulemanager::manager.tabs.inactive'))
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', [CoreModule::STATUS_INSTALLED, CoreModule::STATUS_UNINSTALLED])),
            'core' => Tab::make(__('modulemanager::manager.tabs.core'))
                ->modifyQueryUsing(fn (Builder $query) => $query->where('type', 'core')),
        ];
    }
}
