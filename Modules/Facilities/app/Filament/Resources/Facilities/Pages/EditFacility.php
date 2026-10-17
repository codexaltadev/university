<?php

namespace Modules\Facilities\Filament\Resources\Facilities\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Modules\Facilities\Filament\Resources\Facilities\FacilityResource;

class EditFacility extends EditRecord
{
    protected static string $resource = FacilityResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
