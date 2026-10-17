<?php

namespace Modules\Accreditation\Filament\Resources\Accreditations\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Modules\Accreditation\Filament\Resources\Accreditations\AccreditationResource;

class EditAccreditation extends EditRecord
{
    protected static string $resource = AccreditationResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
