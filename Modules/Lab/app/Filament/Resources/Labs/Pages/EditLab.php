<?php

namespace Modules\Lab\Filament\Resources\Labs\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Modules\Lab\Filament\Resources\Labs\LabResource;

class EditLab extends EditRecord
{
    protected static string $resource = LabResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
