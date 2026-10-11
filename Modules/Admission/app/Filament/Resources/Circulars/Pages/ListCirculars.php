<?php

namespace Modules\Admission\Filament\Resources\Circulars\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Admission\Filament\Resources\Circulars\CircularResource;

class ListCirculars extends ListRecords
{
    protected static string $resource = CircularResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
