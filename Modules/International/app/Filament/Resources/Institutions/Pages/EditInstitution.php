<?php

namespace Modules\International\Filament\Resources\Institutions\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Modules\International\Filament\Resources\Institutions\InstitutionResource;

class EditInstitution extends EditRecord
{
    protected static string $resource = InstitutionResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
