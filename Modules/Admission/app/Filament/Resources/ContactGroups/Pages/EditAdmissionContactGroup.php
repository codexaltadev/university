<?php

namespace Modules\Admission\Filament\Resources\ContactGroups\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Modules\Admission\Filament\Resources\ContactGroups\AdmissionContactGroupResource;

class EditAdmissionContactGroup extends EditRecord
{
    protected static string $resource = AdmissionContactGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
