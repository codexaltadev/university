<?php

namespace Modules\Transport\Filament\Resources\Stops\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Modules\Transport\Filament\Resources\Stops\StopResource;

class ManageStops extends ManageRecords
{
    protected static string $resource = StopResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
