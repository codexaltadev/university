<?php

namespace Modules\ModuleManager\Filament\Resources\Modules\Pages;

use Filament\Resources\Pages\ViewRecord;
use Modules\ModuleManager\Filament\Resources\Modules\ModuleActions;
use Modules\ModuleManager\Filament\Resources\Modules\ModuleResource;

class ViewModule extends ViewRecord
{
    protected static string $resource = ModuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ModuleActions::activate(),
            ModuleActions::deactivate(),
            ModuleActions::uninstall(),
        ];
    }
}
