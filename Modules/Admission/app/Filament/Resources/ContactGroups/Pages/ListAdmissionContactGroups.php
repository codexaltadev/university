<?php

namespace Modules\Admission\Filament\Resources\ContactGroups\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Admission\Filament\Resources\ContactGroups\AdmissionContactGroupResource;

class ListAdmissionContactGroups extends ListRecords
{
    protected static string $resource = AdmissionContactGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
