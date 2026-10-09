<?php

namespace Modules\Faculty\Filament\Resources\Faculties\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Faculty\Filament\Resources\Faculties\FacultyResource;

class ListFaculties extends ListRecords
{
    protected static string $resource = FacultyResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
