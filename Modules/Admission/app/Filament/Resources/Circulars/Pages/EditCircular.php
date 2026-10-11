<?php

namespace Modules\Admission\Filament\Resources\Circulars\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Modules\Admission\Filament\Resources\Circulars\CircularResource;

class EditCircular extends EditRecord
{
    protected static string $resource = CircularResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
