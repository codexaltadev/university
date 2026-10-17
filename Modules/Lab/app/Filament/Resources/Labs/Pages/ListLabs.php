<?php

namespace Modules\Lab\Filament\Resources\Labs\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Lab\Filament\Resources\Labs\LabResource;

class ListLabs extends ListRecords
{
    protected static string $resource = LabResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
