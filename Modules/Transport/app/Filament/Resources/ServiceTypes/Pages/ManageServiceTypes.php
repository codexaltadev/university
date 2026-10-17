<?php

namespace Modules\Transport\Filament\Resources\ServiceTypes\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Modules\Transport\Filament\Resources\ServiceTypes\ServiceTypeResource;

class ManageServiceTypes extends ManageRecords
{
    protected static string $resource = ServiceTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
